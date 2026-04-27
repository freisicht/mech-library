<?php

declare(strict_types=1);

namespace App\Web\Repository;

final class FilterInput
{
    public ?int $pvMin = null;

    public ?int $pvMax = null;

    public ?int $moveMin = null;

    public ?int $moveMax = null;

    public bool $jumpOnly = false;

    public ?int $tmmMin = null;

    public ?int $dmgSMin = null;

    public ?int $dmgSMax = null;

    public ?int $dmgMMin = null;

    public ?int $dmgMMax = null;

    public ?int $dmgLMin = null;

    public ?int $dmgLMax = null;

    public ?int $typeId = null;

    /** @var list<int> */
    public array $roleIds = [];

    /** @var list<int> */
    public array $techIds = [];

    public ?int $eraId = null;

    public ?int $yearMin = null;

    public ?int $yearMax = null;

    /** @var list<array{tag: string, val: int|null}> */
    public array $abilities = [];

    public string $abilityMode = 'and';

    public ?string $search = null;

    public int $page = 1;

    public int $perPage = 50;

    public string $sort = 'name';

    public string $dir = 'asc';

    /** @param array<string, mixed> $params */
    public static function fromQueryParams(array $params): self
    {
        $f = new self();

        $int = static fn (mixed $v): ?int => is_numeric($v) ? (int) $v : null;

        $f->pvMin  = $int($params['pv_min'] ?? null);
        $f->pvMax  = $int($params['pv_max'] ?? null);
        $f->moveMin = $int($params['move_min'] ?? null);
        $f->moveMax = $int($params['move_max'] ?? null);
        $f->jumpOnly = !empty($params['jump']);
        $f->tmmMin = $int($params['tmm_min'] ?? null);
        $f->dmgSMin = $int($params['s_min'] ?? null);
        $f->dmgSMax = $int($params['s_max'] ?? null);
        $f->dmgMMin = $int($params['m_min'] ?? null);
        $f->dmgMMax = $int($params['m_max'] ?? null);
        $f->dmgLMin = $int($params['l_min'] ?? null);
        $f->dmgLMax = $int($params['l_max'] ?? null);
        $f->typeId = $int($params['type'] ?? null);

        $roles = $params['role'] ?? [];
        $f->roleIds = is_array($roles)
            ? array_values(array_map(intval(...), $roles))
            : [];

        $techs = $params['tech'] ?? [];
        $f->techIds = is_array($techs)
            ? array_values(array_map(intval(...), $techs))
            : [];

        $f->eraId   = $int($params['era'] ?? null);
        $f->yearMin = $int($params['year_min'] ?? null);
        $f->yearMax = $int($params['year_max'] ?? null);

        $rawAbilities = $params['ability'] ?? [];
        if (is_array($rawAbilities)) {
            foreach ($rawAbilities as $raw) {
                $raw   = (string) $raw;
                $parts = explode(':', $raw, 2);
                // Only uppercase alphanumeric + slash — the full set of valid ability tag chars.
                $tag = preg_replace('/[^A-Z0-9\/]/', '', strtoupper($parts[0])) ?? '';
                if ($tag === '') {
                    continue;
                }
                $val = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;
                $f->abilities[] = ['tag' => $tag, 'val' => $val];
            }
        }

        $f->abilityMode = (($params['ability_mode'] ?? 'and') === 'or') ? 'or' : 'and';

        $search   = trim((string) ($params['search'] ?? ''));
        $f->search = $search !== '' ? $search : null;

        $rawPage = $params['page'] ?? 1;
        $f->page = is_numeric($rawPage) && (int) $rawPage > 0 ? (int) $rawPage : 1;

        $rawSize = $params['size'] ?? 50;
        $size    = is_numeric($rawSize) ? (int) $rawSize : 50;
        $f->perPage = in_array($size, [25, 50, 100], true) ? $size : 50;

        $allowedSorts = ['name', 'class', 'tonnage', 'pv', 'move', 'tmm', 'dmg_s', 'dmg_m', 'dmg_l', 'armor', 'structure'];

        // Accept both ?sort=name&dir=asc and Tabulator's ?sort[0][field]=name&sort[0][dir]=asc
        $rawSort = $params['sort'] ?? 'name';
        $rawDir  = $params['dir'] ?? 'asc';
        if (is_array($rawSort)) {
            $first   = $rawSort[0] ?? $rawSort[array_key_first($rawSort)] ?? null;
            if (is_array($first)) {
                $rawSort = $first['field'] ?? 'name';
                $rawDir  = $first['dir']   ?? $rawDir;
            } else {
                $rawSort = 'name';
            }
        }
        $sort    = is_string($rawSort) ? $rawSort : 'name';
        $f->sort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $f->dir  = (is_string($rawDir) && $rawDir === 'desc') ? 'desc' : 'asc';

        return $f;
    }
}
