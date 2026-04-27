<?php

declare(strict_types=1);

namespace App\Web\Repository;

use App\Model\Armies;
use PDO;
use Propel\Runtime\Propel;

final class ArmyRepository
{
    /** @return list<array{id: int, name: string, formations: list<array<string, mixed>>}> */
    public function findAll(): array
    {
        $armies = $this->exec(
            'SELECT id, name FROM armies ORDER BY name',
            [],
        )->fetchAll(PDO::FETCH_ASSOC);

        if ($armies === []) {
            return [];
        }

        $armyIds    = array_column($armies, 'id');
        $placeholders = implode(',', array_fill(0, count($armyIds), '?'));

        $formations = $this->exec(
            "SELECT id, army_id, name, formation_type, pv_budget, is_active
             FROM formations
             WHERE army_id IN ({$placeholders})
             ORDER BY name",
            $armyIds,
        )->fetchAll(PDO::FETCH_ASSOC);

        $byArmy = [];
        foreach ($formations as $f) {
            $byArmy[(int) $f['army_id']][] = $f;
        }

        foreach ($armies as &$army) {
            $army['formations'] = $byArmy[(int) $army['id']] ?? [];
        }
        unset($army);

        /** @var list<array{id: int, name: string, formations: list<array<string, mixed>>}> $armies */
        return $armies;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->exec(
            'SELECT id, name, created_at FROM armies WHERE id = :id',
            [':id' => $id],
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $name): int
    {
        $army = new Armies();
        $army->setName($name);
        $army->save();
        return $army->getId();
    }

    public function rename(int $id, string $name): void
    {
        $this->exec(
            'UPDATE armies SET name = :name WHERE id = :id',
            [':name' => $name, ':id' => $id],
        );
    }

    public function delete(int $id): void
    {
        $this->exec('DELETE FROM armies WHERE id = :id', [':id' => $id]);
    }

    /** @param array<int|string, mixed> $params */
    private function exec(string $sql, array $params): \Propel\Runtime\Connection\StatementInterface
    {
        $conn = Propel::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
