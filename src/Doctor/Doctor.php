<?php

declare(strict_types=1);

namespace App\Doctor;

use App\Auth\AuthConfig;
use App\Crawler\MulClient;

/**
 * Read-only setup check behind `bin/console app:doctor`. Makes no network
 * requests apart from the database connection.
 */
final class Doctor
{
    private const MIN_PHP = '8.3.0';

    private const EXTENSIONS = ['curl', 'gd', 'iconv', 'json', 'mbstring', 'pdo_mysql', 'xml', 'zlib'];

    /** @var list<CheckResult> */
    private array $results = [];

    /** @param array<string, string> $env */
    public function __construct(
        private readonly array $env,
        private readonly string $rootDir,
    ) {
    }

    /** @return list<CheckResult> */
    public function run(): array
    {
        $this->results = [];
        $this->checkPhp();
        $this->checkConfig();
        $pdo = $this->checkDatabase();
        $this->checkAuth($pdo);
        $this->checkCrawler();
        $this->checkDirectories();
        return $this->results;
    }

    private function checkPhp(): void
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
            $this->fail('PHP', 'PHP ' . PHP_VERSION . ' is too old', 'Mech Library needs PHP ' . self::MIN_PHP . ' or newer.');
        } else {
            $this->ok('PHP', 'PHP ' . PHP_VERSION);
        }
        $missing = array_values(array_filter(self::EXTENSIONS, static fn (string $e): bool => !extension_loaded($e)));
        if ($missing === []) {
            $this->ok('PHP', 'Extensions: ' . implode(', ', self::EXTENSIONS));
        } else {
            $this->fail('PHP', 'Missing extensions: ' . implode(', ', $missing), 'Install them for the PHP that runs the web server, too.');
        }
    }

    private function checkConfig(): void
    {
        if ($this->env('DB_USER') === null) {
            $hint = is_file($this->rootDir . '/.env')
                ? 'Set DB_USER and the other DB_* values in .env.'
                : 'Copy .env.example to .env and fill in the DB_* values.';
            $this->fail('Config', 'DB_USER is not set', $hint);
        }

        if (filter_var($this->env('APP_DEBUG') ?? 'true', FILTER_VALIDATE_BOOLEAN)) {
            $this->warn('Config', 'APP_DEBUG is on', 'Error pages show stack traces. Set APP_DEBUG=false if others can reach the site.');
        } else {
            $this->ok('Config', 'APP_DEBUG is off');
        }
    }

    private function checkDatabase(): ?\PDO
    {
        if ($this->env('DB_USER') === null) {
            return null;
        }
        $host = $this->env('DB_HOST') ?? '127.0.0.1';
        $port = $this->env('DB_PORT') ?? '3306';
        $name = $this->env('DB_NAME') ?? 'mech_library';
        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $this->env('DB_USER'),
                $this->env('DB_PASS') ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5],
            );
        } catch (\PDOException $e) {
            $this->fail('Database', "Cannot connect to {$name} on {$host}:{$port}", $e->getMessage());
            return null;
        }
        $this->ok('Database', "Connected to {$name} on {$host}:{$port}");

        $this->checkMigrations($pdo);
        return $pdo;
    }

    private function checkMigrations(\PDO $pdo): void
    {
        $available = [];
        foreach (glob($this->rootDir . '/data/migrations/PropelMigration_*.php') ?: [] as $file) {
            if (preg_match('/PropelMigration_(\d+)/', basename($file), $m) === 1) {
                $available[] = (int) $m[1];
            }
        }
        try {
            $applied = array_map('intval', $pdo->query('SELECT version FROM propel_migration')->fetchAll(\PDO::FETCH_COLUMN));
        } catch (\PDOException) {
            $applied = [];
        }
        $pending = array_diff($available, $applied);
        if ($pending === []) {
            $this->ok('Database', count($available) . ' migrations applied');
            $this->checkContent($pdo);
            return;
        }
        $this->fail('Database', count($pending) . ' of ' . count($available) . ' migrations not applied', 'Run vendor/bin/perpl migration:migrate');
    }

    private function checkContent(\PDO $pdo): void
    {
        $units = (int) $pdo->query('SELECT COUNT(*) FROM units')->fetchColumn();
        if ($units === 0) {
            $this->warn('Data', 'No units imported yet', 'Run bin/console mul:sync (takes long, see README).');
            return;
        }
        $this->ok('Data', number_format($units) . ' units');

        $images = count(glob($this->rootDir . '/public/images/units/*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE) ?: []);
        if ($images === 0) {
            $this->warn('Data', 'No unit images downloaded', 'Run bin/console mul:images');
        } else {
            $this->ok('Data', number_format($images) . ' unit images');
        }
    }

    private function checkAuth(?\PDO $pdo): void
    {
        try {
            $config = AuthConfig::resolve($this->env('AUTH_PROVIDER') ?? '', $this->env('MM_BASE_URL') ?? '');
        } catch (\RuntimeException $e) {
            $this->fail('Login', $e->getMessage());
            return;
        }
        $how = $this->env('AUTH_PROVIDER') === null ? ' (AUTH_PROVIDER not set, derived from MM_BASE_URL)' : '';
        $this->ok('Login', "Provider: {$config->provider}{$how}");

        match ($config->provider) {
            AuthConfig::LOCAL      => $this->checkLocal($pdo),
            AuthConfig::DISCORD    => $this->checkDiscord(),
            AuthConfig::MATTERMOST => $this->checkMattermost(),
            default                => null,
        };
    }

    private function checkLocal(?\PDO $pdo): void
    {
        if ($pdo === null) {
            $this->warn('Login', 'Local accounts not checked, no database connection');
            return;
        }
        try {
            $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE provider = 'local'")->fetchColumn();
        } catch (\PDOException) {
            return; // users table missing, already reported as pending migration
        }
        if ($count === 0) {
            $this->fail('Login', 'No local accounts, nobody can log in', 'Run bin/console user:add <name>');
        } else {
            $this->ok('Login', "{$count} local account" . ($count === 1 ? '' : 's'));
        }
    }

    private function checkDiscord(): void
    {
        $ok = $this->requireVars('Login', ['DISCORD_CLIENT_ID', 'DISCORD_CLIENT_SECRET', 'DISCORD_REDIRECT_URI', 'DISCORD_ALLOWED_GUILDS'], 'see docs/AUTH.md, section Discord');
        $this->checkRedirect('DISCORD_REDIRECT_URI');

        $guilds = array_values(array_filter(array_map('trim', explode(',', $this->env('DISCORD_ALLOWED_GUILDS') ?? ''))));
        $bad    = array_values(array_filter($guilds, static fn (string $g): bool => preg_match('/^\d{17,20}$/', $g) !== 1));
        if ($bad !== []) {
            $this->fail('Login', 'Not a Discord server ID: ' . implode(', ', $bad), 'Server IDs are 17 to 20 digits, copied via right-click on the server with Developer Mode on.');
        } elseif ($ok) {
            $this->ok('Login', 'Discord configured, ' . count($guilds) . ' allowed server' . (count($guilds) === 1 ? '' : 's'));
        }
    }

    private function checkMattermost(): void
    {
        $ok = $this->requireVars('Login', ['MM_BASE_URL', 'MM_OAUTH_CLIENT_ID', 'MM_OAUTH_CLIENT_SECRET', 'MM_OAUTH_REDIRECT_URI'], 'see docs/AUTH.md, section Mattermost');
        $this->checkRedirect('MM_OAUTH_REDIRECT_URI');
        if ($ok) {
            $this->ok('Login', 'Mattermost configured for ' . parse_url((string) $this->env('MM_BASE_URL'), PHP_URL_HOST));
        }
    }

    /** @param list<string> $keys */
    private function requireVars(string $section, array $keys, string $hint): bool
    {
        $missing = array_values(array_filter($keys, fn (string $k): bool => $this->env($k) === null));
        if ($missing !== []) {
            $this->fail($section, 'Not set: ' . implode(', ', $missing), ucfirst($hint) . '.');
        }
        return $missing === [];
    }

    private function checkRedirect(string $key): void
    {
        $uri = $this->env($key);
        if ($uri === null) {
            return;
        }
        $parts = parse_url($uri);
        if (!is_array($parts) || ($parts['path'] ?? '') !== '/auth/callback') {
            $this->fail('Login', "{$key} must end in /auth/callback", "Currently: {$uri}");
            return;
        }
        $host = $parts['host'] ?? '';
        if (($parts['scheme'] ?? '') !== 'https' && $host !== 'localhost' && $host !== '127.0.0.1') {
            $this->warn('Login', "{$key} is not https", 'Session cookies are only marked Secure over HTTPS.');
        }
    }

    private function checkCrawler(): void
    {
        $base = $this->env('MUL_BASE_URL');
        if ($base !== null && parse_url($base, PHP_URL_HOST) === 'masterunitlist.info') {
            $this->warn('Crawler', 'MUL_BASE_URL points to the old masterunitlist.info', 'Use https://masterunitlist.battletech.com or remove the line.');
        }
        $delay = $this->env('MUL_REQUEST_DELAY_MS');
        if ($delay !== null && (int) $delay < MulClient::MIN_DELAY_MS) {
            $this->warn('Crawler', "MUL_REQUEST_DELAY_MS={$delay} is below the minimum", 'It is raised to ' . MulClient::MIN_DELAY_MS . ' ms anyway, as MUL robots.txt asks.');
        } else {
            $this->ok('Crawler', 'Request delay ' . max(MulClient::MIN_DELAY_MS, (int) $delay) . ' ms');
        }
    }

    private function checkDirectories(): void
    {
        $raw = $this->env('MUL_RAW_CACHE_DIR') ?? 'data/raw';
        $dirs = [
            'var/cache'          => true,
            'var/log'            => true,
            $raw                 => false,
            'public/images/units' => false,
        ];
        $user = function_exists('posix_geteuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'this user') : 'this user';
        foreach ($dirs as $dir => $mustExist) {
            $path = str_starts_with($dir, '/') ? $dir : $this->rootDir . '/' . $dir;
            if (!is_dir($path)) {
                $mustExist
                    ? $this->fail('Directories', "{$dir} is missing", "Create it and make it writable for the web server.")
                    : $this->warn('Directories', "{$dir} does not exist yet", 'It is created on first use if the parent directory is writable.');
            } elseif (!is_writable($path)) {
                $this->fail('Directories', "{$dir} is not writable for {$user}");
            } else {
                $this->ok('Directories', "{$dir} writable for {$user}");
            }
        }
    }

    private function env(string $key): ?string
    {
        $v = $this->env[$key] ?? null;
        return $v === null || $v === '' ? null : $v;
    }

    private function ok(string $section, string $message): void
    {
        $this->results[] = new CheckResult($section, Status::Ok, $message);
    }

    private function warn(string $section, string $message, ?string $hint = null): void
    {
        $this->results[] = new CheckResult($section, Status::Warn, $message, $hint);
    }

    private function fail(string $section, string $message, ?string $hint = null): void
    {
        $this->results[] = new CheckResult($section, Status::Fail, $message, $hint);
    }
}
