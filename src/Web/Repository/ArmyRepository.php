<?php

declare(strict_types=1);

namespace App\Web\Repository;

use App\Auth\ArmyVisibility;
use App\Model\Armies;
use PDO;
use Propel\Runtime\Propel;

final class ArmyRepository
{
    public function __construct(private readonly ArmyVisibility $visibility)
    {
    }

    /** @return list<array{id: int, name: string, formations: list<array<string, mixed>>}> */
    public function findAll(): array
    {
        $vis = $this->visibility->clause('armies');
        $armies = $this->exec(
            'SELECT id, name, required_mm_channel_id
             FROM armies
             WHERE ' . $vis['sql'] . '
             ORDER BY name',
            $vis['params'],
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
            'SELECT id, name, required_mm_channel_id, created_at FROM armies WHERE id = :id',
            [':id' => $id],
        )->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }
        if (!$this->visibility->canAccessArmy(
            isset($row['required_mm_channel_id']) ? (string) $row['required_mm_channel_id'] : null,
        )) {
            return null;
        }
        return $row;
    }

    public function create(string $name, ?string $requiredMmChannelId = null): int
    {
        $army = new Armies();
        $army->setName($name);
        $army->setRequiredMmChannelId($requiredMmChannelId);
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

    public function setRequiredChannel(int $id, ?string $channelId): void
    {
        $this->exec(
            'UPDATE armies SET required_mm_channel_id = :ch WHERE id = :id',
            [':ch' => $channelId, ':id' => $id],
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
