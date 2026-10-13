<?php

declare(strict_types=1);

namespace App\Web\Repository;

use App\Auth\ArmyVisibility;
use App\Formation\FormationRulesEngine;
use App\Model\Formations as FormationsModel;
use App\Model\FormationUnits as FormationUnitsModel;
use PDO;
use Propel\Runtime\Propel;

final class FormationRepository
{
    private const UNIT_COLUMNS = '
        u.id, u.mul_id, u.name, u.class, u.variant, u.tonnage,
        u.bf_type, u.bf_size, u.bf_move, u.bf_tmm AS tmm,
        u.bf_point_value AS pv,
        u.bf_damage_short AS dmg_s, u.bf_damage_short_min,
        u.bf_damage_medium AS dmg_m, u.bf_damage_medium_min,
        u.bf_damage_long AS dmg_l, u.bf_damage_long_min,
        u.bf_damage_extreme, u.bf_damage_extreme_min,
        u.bf_overheat,
        u.bf_armor AS armor,
        u.bf_structure, u.bf_threshold,
        u.bf_abilities,
        u.image_path,
        r.name AS role,
        fu.sort_order';

    public function __construct(
        private readonly FormationRulesEngine $rulesEngine,
        private readonly ArmyVisibility $visibility,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function findActive(): ?array
    {
        $vis = $this->visibility->clause('a');
        /** @var array<string, mixed>|false $row */
        $row = $this->exec(
            'SELECT f.id, f.army_id, f.name, f.formation_type, f.pv_budget, f.is_active
             FROM formations f
             LEFT JOIN armies a ON a.id = f.army_id
             WHERE f.is_active = 1
               AND (f.army_id IS NULL OR ' . $vis['sql'] . ')
             LIMIT 1',
            $vis['params'],
        )->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $vis = $this->visibility->clause('a');
        /** @var array<string, mixed>|false $row */
        $row = $this->exec(
            'SELECT f.id, f.army_id, f.name, f.formation_type, f.pv_budget, f.is_active
             FROM formations f
             LEFT JOIN armies a ON a.id = f.army_id
             WHERE f.id = :id
               AND (f.army_id IS NULL OR ' . $vis['sql'] . ')',
            array_merge([':id' => $id], $vis['params']),
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /** @return list<array<string, mixed>> */
    public function findStandalone(): array
    {
        $rows = $this->exec(
            'SELECT id, army_id, name, formation_type, pv_budget, is_active
             FROM formations WHERE army_id IS NULL ORDER BY name',
            [],
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /**
     * Flat list of all formations with optional army info — used by the force-panel selector.
     * Army-owned formations sort before standalone (army_id IS NULL sorts last).
     *
     * @return list<array{id: int, name: string, army_id: int|null, army_name: string|null}>
     */
    public function findAllForSelector(): array
    {
        $vis = $this->visibility->clause('a');
        $rows = $this->exec(
            'SELECT f.id, f.name, f.army_id, a.name AS army_name
             FROM formations f
             LEFT JOIN armies a ON a.id = f.army_id
             WHERE f.army_id IS NULL OR ' . $vis['sql'] . '
             ORDER BY (f.army_id IS NULL), a.name, f.name',
            $vis['params'],
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array{id: int, name: string, army_id: int|null, army_name: string|null}> $rows */
        return $rows;
    }

    public function create(
        string $name,
        string $formationType,
        ?int $armyId = null,
        ?int $pvBudget = null,
    ): int {
        $f = new FormationsModel();
        $f->setName($name);
        $f->setFormationType($formationType);
        if ($armyId !== null) {
            $f->setArmyId($armyId);
        }
        if ($pvBudget !== null) {
            $f->setPvBudget($pvBudget);
        }
        $f->save();
        return $f->getId();
    }

    public function update(
        int $id,
        string $name,
        string $formationType,
        ?int $pvBudget,
    ): void {
        $this->exec(
            'UPDATE formations
             SET name = :name, formation_type = :type, pv_budget = :pv
             WHERE id = :id',
            [':name' => $name, ':type' => $formationType, ':pv' => $pvBudget, ':id' => $id],
        );
    }

    public function setActive(int $id): void
    {
        // Clear all active flags first, then set the one we want.
        // MySQL lacks a partial unique index, so we manage the invariant here.
        $this->exec('UPDATE formations SET is_active = 0', []);
        $this->exec('UPDATE formations SET is_active = 1 WHERE id = :id', [':id' => $id]);
    }

    public function deactivateAll(): void
    {
        $this->exec('UPDATE formations SET is_active = 0', []);
    }

    public function delete(int $id): void
    {
        $this->exec('DELETE FROM formations WHERE id = :id', [':id' => $id]);
    }

    public function addUnit(int $formationId, int $unitId): void
    {
        $maxOrder = (int) ((string) ($this->exec(
            'SELECT COALESCE(MAX(sort_order), -1) FROM formation_units WHERE formation_id = :f',
            [':f' => $formationId],
        )->fetchColumn() ?? '-1'));

        $fu = new FormationUnitsModel();
        $fu->setFormationId($formationId);
        $fu->setUnitId($unitId);
        $fu->setSortOrder($maxOrder + 1);
        $fu->save();
    }

    public function removeEntry(int $formationId, int $entryId): void
    {
        $this->exec(
            'DELETE FROM formation_units WHERE formation_id = :f AND id = :e',
            [':f' => $formationId, ':e' => $entryId],
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $units = $this->fetchUnits((int) $row['id']);

        $pvSum  = array_sum(array_column($units, 'pv'));
        $budget = $row['pv_budget'] !== null ? (int) $row['pv_budget'] : null;

        $compliance = $this->rulesEngine->evaluate(
            (string) $row['formation_type'],
            $units,
        );

        $row['units']      = $units;
        $row['unit_count'] = count($units);
        $row['pv_sum']     = $pvSum;
        $row['pv_remaining'] = $budget !== null ? $budget - $pvSum : null;
        $row['dmg_s_total']  = array_sum(array_column($units, 'dmg_s'));
        $row['dmg_m_total']  = array_sum(array_column($units, 'dmg_m'));
        $row['dmg_l_total']  = array_sum(array_column($units, 'dmg_l'));
        $row['compliance']   = $compliance->toArray();

        return $row;
    }

    /** @return list<array<string, mixed>> */
    private function fetchUnits(int $formationId): array
    {
        $rows = $this->exec(
            'SELECT fu.id AS entry_id,' . self::UNIT_COLUMNS . '
             FROM formation_units fu
             JOIN units u ON u.id = fu.unit_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE fu.formation_id = :f
             ORDER BY fu.sort_order, fu.id',
            [':f' => $formationId],
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $rows */
        return $rows;
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
