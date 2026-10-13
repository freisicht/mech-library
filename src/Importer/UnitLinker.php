<?php

declare(strict_types=1);

namespace App\Importer;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Propel;

/**
 * Attaches the relaunched MUL's public ids to units imported from the old MUL.
 *
 * Old and new MUL share the display name ("Atlas AS7-D"), so a unit without
 * `mul_uid` is linked when exactly one snapshot unit and exactly one local
 * unit carry that name. Ambiguous names are left alone.
 */
final class UnitLinker
{
    public function __construct(private readonly ?ConnectionInterface $conn = null)
    {
    }

    /**
     * @param list<array<string, mixed>> $snapshotUnits
     * @return array{linked: int, ambiguous: int}
     */
    public function link(array $snapshotUnits): array
    {
        $conn = $this->conn ?? Propel::getConnection();

        $stmt = $conn->prepare('SELECT mul_uid FROM units WHERE mul_uid IS NOT NULL');
        $stmt->execute();
        $taken = array_flip($stmt->fetchAll(\PDO::FETCH_COLUMN));

        $snapshotByName = [];
        foreach ($snapshotUnits as $unit) {
            $snapshotByName[LookupResolver::normalise(SnapshotUnitMapper::displayName($unit))][] = (string) $unit['id'];
        }

        $stmt = $conn->prepare('SELECT id, name FROM units WHERE mul_uid IS NULL');
        $stmt->execute();
        $localByName = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $localByName[LookupResolver::normalise((string) $row['name'])][] = (int) $row['id'];
        }

        $counts = ['linked' => 0, 'ambiguous' => 0];
        $update = $conn->prepare('UPDATE units SET mul_uid = ? WHERE id = ?');
        foreach ($localByName as $name => $ids) {
            $uids = $snapshotByName[$name] ?? [];
            if ($uids === []) {
                continue;
            }
            if (count($ids) !== 1 || count($uids) !== 1 || isset($taken[$uids[0]])) {
                $counts['ambiguous']++;
                continue;
            }
            $update->execute([$uids[0], $ids[0]]);
            $counts['linked']++;
        }

        return $counts;
    }
}
