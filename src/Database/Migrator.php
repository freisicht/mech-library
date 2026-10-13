<?php

declare(strict_types=1);

namespace App\Database;

use Propel\Runtime\Connection\ConnectionInterface;
use RuntimeException;

final class Migrator
{
    public function __construct(
        private readonly ConnectionInterface $conn,
        private readonly string $dir,
        private readonly string $table = 'schema_migrations',
    ) {
        if (preg_match('/^\w+$/', $table) !== 1) {
            throw new RuntimeException("invalid table name: {$table}");
        }
    }

    /** @return list<string> file names, sorted */
    public function available(): array
    {
        $files = array_map(basename(...), glob($this->dir . '/[0-9][0-9][0-9]_*.sql') ?: []);
        sort($files);
        return $files;
    }

    /** @return list<string> */
    public function pending(): array
    {
        $applied = $this->applied();
        return array_values(array_filter($this->available(), static fn (string $f) => !in_array($f, $applied, true)));
    }

    /**
     * Marks every migration up to and including $upTo (e.g. "003") as applied without
     * running it. For databases that were migrated by hand before this table existed.
     *
     * @return list<string>
     */
    public function baseline(string $upTo): array
    {
        $marked = [];
        foreach ($this->pending() as $file) {
            if (strcmp(substr($file, 0, 3), $upTo) <= 0) {
                $this->record($file);
                $marked[] = $file;
            }
        }
        return $marked;
    }

    public function apply(string $file): void
    {
        $sql = (string) file_get_contents($this->dir . '/' . $file);
        foreach (self::split($sql) as $statement) {
            $this->conn->exec($statement);
        }
        $this->record($file);
    }

    /** @return list<string> */
    public static function split(string $sql): array
    {
        $sql = (string) preg_replace('/(^|\s)-- .*$/m', '$1', $sql);
        $parts = array_map(trim(...), preg_split('/;\s*$/m', $sql) ?: []);
        return array_values(array_filter($parts, static fn (string $s) => $s !== ''));
    }

    /** @return list<string> */
    private function applied(): array
    {
        $this->ensureTable();
        $stmt = $this->conn->query("SELECT name FROM {$this->table}");
        /** @var list<string> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $rows;
    }

    private function record(string $file): void
    {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name) VALUES (:n)");
        $stmt->execute([':n' => $file]);
    }

    private function ensureTable(): void
    {
        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS {$this->table} (
                name        VARCHAR(191) NOT NULL,
                applied_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        );
    }
}
