<?php

declare(strict_types=1);

namespace App\Web\Repository;

use PDO;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Propel;

final class UnitRepository
{
    private const JOINS = '
        LEFT JOIN roles r ON r.id = u.role_id
        LEFT JOIN technologies tech ON tech.id = u.technology_id
        LEFT JOIN unit_types ut ON ut.id = u.type_id
        LEFT JOIN eras e ON e.id = u.era_id';

    private const LIST_COLUMNS = '
        u.id, u.name, u.class, u.variant, u.tonnage,
        u.bf_point_value AS pv, u.bf_move AS move, u.bf_tmm AS tmm,
        u.bf_damage_short AS dmg_s, u.bf_damage_medium AS dmg_m,
        u.bf_damage_long AS dmg_l, u.bf_armor AS armor,
        u.bf_structure AS `structure`, u.bf_abilities AS abilities,
        u.era_start, r.name AS role_name, tech.name AS tech_name,
        ut.name AS type_name, e.name AS era_name';

    /** @return array{total: int, last_page: int, data: list<array<string, mixed>>} */
    public function findFiltered(FilterInput $f): array
    {
        [$where, $params] = $this->buildWhere($f);

        $total = (int) ((string) ($this->exec(
            'SELECT COUNT(*) FROM units u' . self::JOINS . $where,
            $params,
        )->fetchColumn() ?? '0'));

        $sortCol = $this->sortColumn($f->sort);
        $sortDir = $f->dir === 'desc' ? 'DESC' : 'ASC';
        $limit   = $f->perPage;
        $offset  = ($f->page - 1) * $f->perPage;

        // LIMIT/OFFSET are PHP ints — safe to embed directly; named params bind as strings in MySQL.
        $rows = $this->exec(
            'SELECT ' . self::LIST_COLUMNS
                . ' FROM units u' . self::JOINS . $where
                . " ORDER BY {$sortCol} {$sortDir}"
                . " LIMIT {$limit} OFFSET {$offset}",
            $params,
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $rows */
        return [
            'total'     => $total,
            'last_page' => max(1, (int) ceil($total / $f->perPage)),
            'data'      => $rows,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->exec(
            'SELECT u.*, r.name AS role_name, tech.name AS tech_name,
                ut.name AS type_name, e.name AS era_name
            FROM units u' . self::JOINS . '
            WHERE u.id = :id',
            [':id' => $id],
        )->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }
        $abilities = $this->exec(
            'SELECT tag, `short`, `medium`, `long`, value, raw
            FROM unit_abilities WHERE unit_id = :id ORDER BY id',
            [':id' => $id],
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $abilities */
        $row['parsed_abilities'] = $abilities;

        return $row;
    }

    public function getFilterOptions(): FilterOptions
    {
        /** @var list<array{id: int, name: string}> $types */
        $types = $this->exec('SELECT id, name FROM unit_types ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array{id: int, name: string}> $roles */
        $roles = $this->exec('SELECT id, name FROM roles ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array{id: int, name: string}> $techs */
        $techs = $this->exec('SELECT id, name FROM technologies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array{id: int, name: string, start_year: int|null}> $eras */
        $eras = $this->exec(
            // MUL /Era/Index carries no years, so fall back to the earliest unit of each era.
            'SELECT e.id, e.name, e.start_year FROM eras e
             LEFT JOIN (SELECT era_id, MIN(era_start) AS first_year FROM units GROUP BY era_id) fy
                    ON fy.era_id = e.id
             ORDER BY COALESCE(e.start_year, fy.first_year) IS NULL, COALESCE(e.start_year, fy.first_year), e.name',
        )->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<string> $tags */
        $tags = $this->exec(
            'SELECT DISTINCT tag FROM unit_abilities ORDER BY tag',
        )->fetchAll(PDO::FETCH_COLUMN);

        return new FilterOptions($types, $roles, $techs, $eras, $tags);
    }

    /**
     * Build the WHERE clause and named PDO params from the filter.
     *
     * @return array{string, array<string, int|string>}
     */
    private function buildWhere(FilterInput $f): array
    {
        $conds  = [];
        $params = [];

        if ($f->pvMin !== null) {
            $conds[]           = 'u.bf_point_value >= :pv_min';
            $params[':pv_min'] = $f->pvMin;
        }
        if ($f->pvMax !== null) {
            $conds[]           = 'u.bf_point_value <= :pv_max';
            $params[':pv_max'] = $f->pvMax;
        }
        if ($f->moveMin !== null) {
            $conds[]             = 'CAST(u.bf_move AS UNSIGNED) >= :move_min';
            $params[':move_min'] = $f->moveMin;
        }
        if ($f->moveMax !== null) {
            $conds[]             = 'CAST(u.bf_move AS UNSIGNED) <= :move_max';
            $params[':move_max'] = $f->moveMax;
        }
        if ($f->jumpOnly) {
            $conds[] = "u.bf_move LIKE '%j%'";
        }
        if ($f->tmmMin !== null) {
            $conds[]            = 'u.bf_tmm >= :tmm_min';
            $params[':tmm_min'] = $f->tmmMin;
        }
        if ($f->dmgSMin !== null) {
            $conds[]         = 'u.bf_damage_short >= :s_min';
            $params[':s_min'] = $f->dmgSMin;
        }
        if ($f->dmgSMax !== null) {
            $conds[]         = 'u.bf_damage_short <= :s_max';
            $params[':s_max'] = $f->dmgSMax;
        }
        if ($f->dmgMMin !== null) {
            $conds[]         = 'u.bf_damage_medium >= :m_min';
            $params[':m_min'] = $f->dmgMMin;
        }
        if ($f->dmgMMax !== null) {
            $conds[]         = 'u.bf_damage_medium <= :m_max';
            $params[':m_max'] = $f->dmgMMax;
        }
        if ($f->dmgLMin !== null) {
            $conds[]         = 'u.bf_damage_long >= :l_min';
            $params[':l_min'] = $f->dmgLMin;
        }
        if ($f->dmgLMax !== null) {
            $conds[]         = 'u.bf_damage_long <= :l_max';
            $params[':l_max'] = $f->dmgLMax;
        }
        if ($f->typeId !== null) {
            $conds[]            = 'u.type_id = :type_id';
            $params[':type_id'] = $f->typeId;
        }
        if ($f->roleIds !== []) {
            $ph = implode(',', array_map(static fn (int $i) => ":role_{$i}", array_keys($f->roleIds)));
            $conds[] = "u.role_id IN ({$ph})";
            foreach ($f->roleIds as $i => $rid) {
                $params[":role_{$i}"] = $rid;
            }
        }
        if ($f->techIds !== []) {
            $ph = implode(',', array_map(static fn (int $i) => ":tech_{$i}", array_keys($f->techIds)));
            $conds[] = "u.technology_id IN ({$ph})";
            foreach ($f->techIds as $i => $tid) {
                $params[":tech_{$i}"] = $tid;
            }
        }
        $yearActive = $f->yearMin !== null || $f->yearMax !== null;
        if ($f->eraIds !== [] && !$yearActive) {
            $ph = implode(',', array_map(static fn (int $i) => ":era_{$i}", array_keys($f->eraIds)));
            $conds[] = "u.era_id IN ({$ph})";
            foreach ($f->eraIds as $i => $eid) {
                $params[":era_{$i}"] = $eid;
            }
        }
        if ($f->yearMin !== null) {
            $conds[]             = 'u.era_start >= :year_min';
            $params[':year_min'] = $f->yearMin;
        }
        if ($f->yearMax !== null) {
            $conds[]             = 'u.era_start <= :year_max';
            $params[':year_max'] = $f->yearMax;
        }
        if ($f->search !== null) {
            $conds[]          = '(u.name LIKE :search OR u.class LIKE :search OR u.variant LIKE :search)';
            $params[':search'] = '%' . $f->search . '%';
        }

        $this->applyAbilityFilter($f, $conds);

        $where = $conds !== [] ? ' WHERE ' . implode(' AND ', $conds) : '';

        return [$where, $params];
    }

    /**
     * Append ability EXISTS conditions.
     * Tag strings are sanitised in FilterInput (uppercase alphanum+slash only).
     * Numeric thresholds are always cast to int before interpolation.
     *
     * @param list<string> $conds
     */
    private function applyAbilityFilter(FilterInput $f, array &$conds): void
    {
        if ($f->abilities === []) {
            return;
        }

        if ($f->abilityMode === 'or') {
            $orParts = [];
            foreach ($f->abilities as $ab) {
                $tag  = $ab['tag'];
                $part = "ua_or.tag = '{$tag}'";
                if ($ab['val'] !== null) {
                    $v    = (int) $ab['val'];
                    $part = "({$part} AND (CAST(ua_or.value AS UNSIGNED) >= {$v}"
                        . " OR COALESCE(ua_or.short, 0) >= {$v}))";
                }
                $orParts[] = $part;
            }
            $conds[] = 'EXISTS (SELECT 1 FROM unit_abilities ua_or'
                . ' WHERE ua_or.unit_id = u.id AND (' . implode(' OR ', $orParts) . '))';

            return;
        }

        foreach ($f->abilities as $i => $ab) {
            $tag   = $ab['tag'];
            $alias = "ua{$i}";
            $sql   = "EXISTS (SELECT 1 FROM unit_abilities {$alias}"
                . " WHERE {$alias}.unit_id = u.id AND {$alias}.tag = '{$tag}'";
            if ($ab['val'] !== null) {
                $v   = (int) $ab['val'];
                $sql .= " AND (CAST({$alias}.value AS UNSIGNED) >= {$v}"
                    . " OR COALESCE({$alias}.short, 0) >= {$v})";
            }
            $sql .= ')';
            $conds[] = $sql;
        }
    }

    private function sortColumn(string $sort): string
    {
        return match ($sort) {
            'class'     => 'u.class',
            'tonnage'   => 'u.tonnage',
            'pv'        => 'u.bf_point_value',
            'move'      => 'CAST(u.bf_move AS UNSIGNED)',
            'tmm'       => 'u.bf_tmm',
            'dmg_s'     => 'u.bf_damage_short',
            'dmg_m'     => 'u.bf_damage_medium',
            'dmg_l'     => 'u.bf_damage_long',
            'armor'     => 'u.bf_armor',
            'structure' => 'u.bf_structure',
            default     => 'u.name',
        };
    }

    /** @param array<string, int|string> $params */
    private function exec(string $sql, array $params = []): StatementInterface
    {
        $stmt = Propel::getConnection('mech_library')->prepare($sql);
        $stmt->execute($params !== [] ? $params : null);

        return $stmt;
    }
}
