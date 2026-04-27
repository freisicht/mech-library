<?php

declare(strict_types=1);

namespace App\Formation;

/**
 * Canonical Alpha Strike formation type definitions.
 *
 * Each entry encodes: slug, display name, faction affinity, typical/min/max unit
 * count, and a list of rule descriptors evaluated by FormationRulesEngine.
 *
 * Rule descriptor shape:
 *   type      — rule type identifier (see FormationRulesEngine)
 *   label     — human-readable requirement shown when unmet
 *   ...params — rule-specific parameters
 *
 * Sources: Alpha Strike Companion (2014), Alpha Strike: Commander's Edition (2021).
 * Rules marked [VERIFY] are best-effort from memory and should be checked against
 * the published rulebook.
 */
final class FormationTypeRegistry
{
    /** @return array<string, array<string, mixed>> keyed by slug */
    public static function all(): array
    {
        return self::IS_LANCES + self::CLAN_STARS + self::COMSTAR_LEVEL_IIS + self::GENERIC;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    // -------------------------------------------------------------------------
    // Inner Sphere — Lance-based (default 4 units, range 2–8)
    // -------------------------------------------------------------------------

    private const IS_LANCES = [
        'battle-lance' => [
            'slug'    => 'battle-lance',
            'name'    => 'Battle Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 2,
                    'label' => 'At least 2 units must be Size 3 (Heavy) or larger',
                ],
            ],
        ],

        'assault-lance' => [
            'slug'    => 'assault-lance',
            'name'    => 'Assault Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed',
                ],
            ],
        ],

        'fire-support-lance' => [
            'slug'    => 'fire-support-lance',
            'name'    => 'Fire Support Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Missile Boat', 'Sniper'],
                    'pct'    => 75,
                    'label'  => 'At least 75 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        'direct-fire-lance' => [
            'slug'    => 'direct-fire-lance',
            'name'    => 'Direct Fire Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Sniper', 'Brawler', 'Juggernaut'],
                    'pct'    => 75,
                    'label'  => 'At least 75 % of units must have Sniper, Brawler, or Juggernaut role',
                ],
            ],
        ],

        'striker-cavalry-lance' => [
            'slug'    => 'striker-cavalry-lance',
            'name'    => 'Striker / Cavalry Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'All units must be Size 3 (Heavy) or smaller',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 10,
                    'jump'  => 8,
                    'pct'   => 75,
                    'label' => 'At least 75 % of units must have Move ≥ 10" or Jump ≥ 8"',
                ],
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Striker', 'Skirmisher'],
                    'pct'    => 50,
                    'label'  => 'At least 50 % of units must have Striker or Skirmisher role',
                ],
            ],
        ],

        'recon-lance' => [
            'slug'    => 'recon-lance',
            'name'    => 'Recon Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_move_all',
                    'move'  => 6,
                    'label' => 'All units must have Move ≥ 6"',
                ],
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Scout', 'Striker'],
                    'pct'    => 50,
                    'label'  => 'At least 50 % of units must have Scout or Striker role',
                ],
            ],
        ],

        'pursuit-lance' => [
            'slug'    => 'pursuit-lance',
            'name'    => 'Pursuit Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 0,
                    'pct'   => 75,
                    'label' => 'At least 75 % of units must have Move ≥ 8"',
                ],
            ],
        ],

        'command-lance' => [
            'slug'    => 'command-lance',
            'name'    => 'Command Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY] Commander designation is not tracked in our DB; no hard rule coded.
            'rules'   => [],
        ],

        'light-lance' => [
            'slug'    => 'light-lance',
            'name'    => 'Light Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 1–2 (Light or Medium)',
                ],
            ],
        ],

        'heavy-lance' => [
            'slug'    => 'heavy-lance',
            'name'    => 'Heavy Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
            ],
        ],

        'ranger-lance' => [
            'slug'    => 'ranger-lance',
            'name'    => 'Ranger Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY]
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Striker', 'Scout'],
                    'pct'    => 50,
                    'label'  => 'At least 50 % of units must have Striker or Scout role',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 0,
                    'pct'   => 50,
                    'label' => 'At least 50 % of units must have Move ≥ 8"',
                ],
            ],
        ],

        'urban-lance' => [
            'slug'    => 'urban-lance',
            'name'    => 'Urban Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY] No strong rule data; city-fighting composition is highly varied
            'rules'   => [],
        ],

        'hunter-lance' => [
            'slug'    => 'hunter-lance',
            'name'    => 'Hunter Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY]
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Striker', 'Skirmisher'],
                    'pct'    => 50,
                    'label'  => 'At least 50 % of units must have Striker or Skirmisher role',
                ],
            ],
        ],

        'security-lance' => [
            'slug'    => 'security-lance',
            'name'    => 'Security Lance',
            'faction' => 'is',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // [VERIFY] Garrison/security forces; no hard AS Companion rule encoded
            'rules'   => [],
        ],
    ];

    // -------------------------------------------------------------------------
    // Clan — Star-based (default 5 units, range 4–5 for Stars; Binaries/Trinaries larger)
    // -------------------------------------------------------------------------

    private const CLAN_STARS = [
        'battle-star' => [
            'slug'    => 'battle-star',
            'name'    => 'Battle Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
            ],
        ],

        'heavy-battle-star' => [
            'slug'    => 'heavy-battle-star',
            'name'    => 'Heavy Battle Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 4,
                    'label' => 'At least 4 units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 4,
                    'count' => 2,
                    'label' => 'At least 2 units must be Size 4 (Assault)',
                ],
            ],
        ],

        'assault-star' => [
            'slug'    => 'assault-star',
            'name'    => 'Assault Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 4,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 4 (Assault)',
                ],
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed',
                ],
            ],
        ],

        'fire-support-star' => [
            'slug'    => 'fire-support-star',
            'name'    => 'Fire Support Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Missile Boat', 'Sniper'],
                    'pct'    => 60,
                    'label'  => 'At least 60 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        'strike-star' => [
            'slug'    => 'strike-star',
            'name'    => 'Strike Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 10,
                    'jump'  => 8,
                    'pct'   => 75,
                    'label' => 'At least 75 % of units must have Move ≥ 10" or Jump ≥ 8"',
                ],
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Striker', 'Skirmisher'],
                    'pct'    => 60,
                    'label'  => 'At least 60 % of units must have Striker or Skirmisher role',
                ],
            ],
        ],

        'recon-star' => [
            'slug'    => 'recon-star',
            'name'    => 'Recon Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_move_all',
                    'move'  => 6,
                    'label' => 'All units must have Move ≥ 6"',
                ],
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Scout', 'Striker'],
                    'pct'    => 60,
                    'label'  => 'At least 60 % of units must have Scout or Striker role',
                ],
            ],
        ],

        'pursuit-star' => [
            'slug'    => 'pursuit-star',
            'name'    => 'Pursuit Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 0,
                    'pct'   => 80,
                    'label' => 'At least 80 % of units must have Move ≥ 8"',
                ],
            ],
        ],

        'command-star' => [
            'slug'    => 'command-star',
            'name'    => 'Command Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            'rules'   => [],
        ],

        'light-battle-star' => [
            'slug'    => 'light-battle-star',
            'name'    => 'Light Battle Star',
            'faction' => 'clan',
            'count'   => ['min' => 4, 'max' => 5, 'typical' => 5],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 1–2 (Light or Medium)',
                ],
            ],
        ],

        'nova' => [
            'slug'    => 'nova',
            'name'    => 'Nova',
            'faction' => 'clan',
            'count'   => ['min' => 5, 'max' => 10, 'typical' => 10],
            // Mixed OmniMech + Battle Armor; BA not yet in scope — flag as informational
            'rules'   => [],
            'note'    => 'Requires OmniMechs + Battle Armor. BA units not yet supported.',
        ],

        'binary' => [
            'slug'    => 'binary',
            'name'    => 'Binary',
            'faction' => 'clan',
            'count'   => ['min' => 8, 'max' => 10, 'typical' => 10],
            'rules'   => [],
        ],

        'trinary' => [
            'slug'    => 'trinary',
            'name'    => 'Trinary',
            'faction' => 'clan',
            'count'   => ['min' => 12, 'max' => 15, 'typical' => 15],
            'rules'   => [],
        ],
    ];

    // -------------------------------------------------------------------------
    // ComStar / Word of Blake — Level II-based (default 6 units)
    // -------------------------------------------------------------------------

    private const COMSTAR_LEVEL_IIS = [
        'battle-level-ii' => [
            'slug'    => 'battle-level-ii',
            'name'    => 'Battle Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
            ],
        ],

        'fire-level-ii' => [
            'slug'    => 'fire-level-ii',
            'name'    => 'Fire Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            // [VERIFY]
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Missile Boat', 'Sniper'],
                    'pct'    => 67,
                    'label'  => 'At least 67 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        'recon-level-ii' => [
            'slug'    => 'recon-level-ii',
            'name'    => 'Recon Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_move_all',
                    'move'  => 6,
                    'label' => 'All units must have Move ≥ 6"',
                ],
            ],
        ],

        'pursuit-level-ii' => [
            'slug'    => 'pursuit-level-ii',
            'name'    => 'Pursuit Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 0,
                    'pct'   => 67,
                    'label' => 'At least 67 % of units must have Move ≥ 8"',
                ],
            ],
        ],

        'assault-level-ii' => [
            'slug'    => 'assault-level-ii',
            'name'    => 'Assault Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            // [VERIFY]
            'rules'   => [
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed',
                ],
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
            ],
        ],

        'support-level-ii' => [
            'slug'    => 'support-level-ii',
            'name'    => 'Support Level II',
            'faction' => 'comstar',
            'count'   => ['min' => 4, 'max' => 6, 'typical' => 6],
            'rules'   => [],
        ],
    ];

    // -------------------------------------------------------------------------
    // Generic / faction-agnostic
    // -------------------------------------------------------------------------

    private const GENERIC = [
        'custom' => [
            'slug'    => 'custom',
            'name'    => 'Custom (no constraints)',
            'faction' => 'any',
            'count'   => ['min' => 1, 'max' => 99, 'typical' => 4],
            'rules'   => [],
        ],
    ];
}
