<?php

declare(strict_types=1);

namespace App\Importer;

use InvalidArgumentException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Propel;

/**
 * Maps lookup names from the relaunched MUL onto local lookup rows.
 *
 * The relaunch renumbered types, roles, technologies and eras, so local ids
 * (taken from the old MUL) cannot be reused. Matching by name keeps existing
 * units and filters intact; unknown names get a new row.
 */
final class LookupResolver implements Lookups
{
    private const TABLES = ['unit_types', 'roles', 'technologies', 'eras'];

    /** @var array<string, array<string, int>> table => normalised name => id */
    private array $cache = [];

    public function __construct(private readonly ?ConnectionInterface $conn = null)
    {
    }

    public function resolve(string $table, string $name): int
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException("Unknown lookup table: {$table}");
        }

        $map = $this->load($table);
        $key = self::normalise($name);
        if (isset($map[$key])) {
            return $map[$key];
        }

        $id = (int) $this->run("SELECT COALESCE(MAX(id), 0) + 1 FROM {$table}")->fetchColumn();
        $this->run("INSERT INTO {$table} (id, name) VALUES (?, ?)", [$id, $name]);

        return $this->cache[$table][$key] = $id;
    }

    public static function normalise(string $name): string
    {
        $name = str_replace(["\u{2013}", "\u{2014}"], '-', $name);
        return mb_strtolower(trim((string) preg_replace('#\s+#u', ' ', $name)));
    }

    /** @return array<string, int> */
    private function load(string $table): array
    {
        if (!isset($this->cache[$table])) {
            $this->cache[$table] = [];
            // Ascending id, later rows win: the old MUL ids are higher than the
            // stub rows test fixtures created, so real data takes precedence.
            foreach ($this->run("SELECT id, name FROM {$table} ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $this->cache[$table][self::normalise((string) $row['name'])] = (int) $row['id'];
            }
        }
        return $this->cache[$table];
    }

    /** @param list<mixed> $params */
    private function run(string $sql, array $params = []): \Propel\Runtime\Connection\StatementInterface
    {
        $stmt = ($this->conn ?? Propel::getConnection())->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
