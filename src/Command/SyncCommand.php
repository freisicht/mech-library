<?php

declare(strict_types=1);

namespace App\Command;

use App\Crawler\MulClient;
use App\Crawler\MulClientFactory;
use App\Crawler\MulSnapshot;
use App\Importer\LookupResolver;
use App\Importer\SnapshotUnitMapper;
use App\Importer\UnitImporter;
use App\Importer\UnitLinker;
use App\Importer\UnitPageParser;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'mul:sync', description: 'Sync units from masterunitlist.battletech.com into MySQL')]
final class SyncCommand extends Command
{
    private const PROGRESS_EVERY = 25;

    protected function configure(): void
    {
        $this
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Comma-separated MUL type ids (see docs/MUL_API.md)', '2,3,4,5,8,10')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Stop after this many units (for trial runs)')
            ->addOption('link-only', null, InputOption::VALUE_NONE, 'Only attach new MUL ids to existing units, fetch no unit pages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new Logger('sync');
        $logger->pushHandler(new StreamHandler('php://stderr', Level::Warning));

        $client   = MulClientFactory::create($logger);
        $snapshot = MulSnapshot::load($client);
        $output->writeln(sprintf('Snapshot: %d units', count($snapshot->units)));

        $linked = (new UnitLinker())->link($snapshot->units);
        $output->writeln(sprintf('Linked existing units: %d (ambiguous names skipped: %d)', $linked['linked'], $linked['ambiguous']));
        if ($input->getOption('link-only')) {
            return Command::SUCCESS;
        }

        $types = array_map('intval', explode(',', (string) $input->getOption('types')));
        $units = array_values(array_filter($snapshot->units, static fn (array $u): bool => in_array((int) $u['t'], $types, true)));
        $limit = $input->getOption('limit');
        if ($limit !== null) {
            $units = array_slice($units, 0, (int) $limit);
        }

        $pageDir = MulClientFactory::cacheDir() . '/units';
        if (!is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        $toFetch = count(array_filter($units, static fn (array $u): bool => !is_file("{$pageDir}/{$u['id']}.json")));
        $output->writeln(sprintf(
            'Units to sync: %d, of which %d need a page fetch (about %s at %d s each)',
            count($units),
            $toFetch,
            gmdate('H:i', $toFetch * intdiv(MulClient::MIN_DELAY_MS, 1000)),
            intdiv(MulClient::MIN_DELAY_MS, 1000),
        ));

        $parser   = new UnitPageParser();
        $mapper   = new SnapshotUnitMapper($snapshot, new LookupResolver());
        $importer = new UnitImporter($logger);
        $counts   = ['changed' => 0, 'unchanged' => 0, 'no_stats' => 0, 'failed' => 0];

        foreach ($units as $i => $unit) {
            $uid = (string) $unit['id'];
            try {
                $stats = $this->stats($client, $parser, "{$pageDir}/{$uid}.json", $uid);
                if ($stats === null) {
                    $counts['no_stats']++;
                } else {
                    $counts[$importer->upsert($mapper->map($unit, $stats)) ? 'changed' : 'unchanged']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                $logger->error('Unit sync failed', ['uid' => $uid, 'error' => $e->getMessage()]);
            }

            if (($i + 1) % self::PROGRESS_EVERY === 0 || $i + 1 === count($units)) {
                $output->writeln(sprintf(
                    '%s  %d/%d  changed %d, unchanged %d, no AS stats %d, failed %d',
                    date('H:i:s'),
                    $i + 1,
                    count($units),
                    $counts['changed'],
                    $counts['unchanged'],
                    $counts['no_stats'],
                    $counts['failed'],
                ));
            }
        }

        return $counts['failed'] === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Parsed page stats, cached as small JSON files so a re-run (or a run on
     * another machine with a copy of the cache) makes no requests.
     *
     * @return array<string, mixed>|null
     */
    private function stats(MulClient $client, UnitPageParser $parser, string $cacheFile, string $uid): ?array
    {
        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            return is_array($cached) ? ($cached['stats'] ?? null) : null;
        }

        $stats = $parser->parse($client->fetchRaw($client->url('u/' . $uid), useCache: false));
        file_put_contents($cacheFile, json_encode(['fetched_at' => date('c'), 'stats' => $stats], JSON_UNESCAPED_SLASHES));

        return $stats;
    }
}
