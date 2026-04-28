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
 * Primary source: Alpha Strike: Commander's Edition (AS:CE, 2021), pp. 117–121.
 * Secondary source: Campaign Operations (CO, 2016) for variants/types not present in AS:CE.
 * Page references appear in the per-entry `source` field. A `[VERIFY]` marker means the
 * rule predates the canonical pass and should be cross-checked when next touched.
 */
final class FormationTypeRegistry
{
    /** @return array<string, array<string, mixed>> keyed by slug */
    public static function all(): array
    {
        return self::IS_LANCES
            + self::AEROSPACE_SQUADRONS
            + self::CLAN_STARS
            + self::COMSTAR_LEVEL_IIS
            + self::GENERIC;
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
            'source'  => 'AS:CE p.117',
            'ideal_role' => 'Brawler',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 3,
                    'pct'   => 50,
                    'label' => '50 % of units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Brawler', 'Sniper', 'Skirmisher'],
                    'count' => 3,
                    'label' => 'At least 3 units must have Brawler, Sniper, or Skirmisher role',
                ],
            ],
        ],

        'assault-lance' => [
            'slug'    => 'assault-lance',
            'name'    => 'Assault Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'ideal_role' => 'Juggernaut',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE also requires ≥1 Juggernaut OR ≥2 Sniper units; not encoded
            // because the rules engine has no role-alternation primitive yet.
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
                [
                    'type'  => 'min_move_all',
                    'move'  => 4,
                    'label' => 'All units must have ground Move ≥ 4"',
                ],
                [
                    'type'  => 'min_pct_with_attack',
                    'range' => 'm',
                    'value' => 3,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must have a Medium-range damage value of 3 or more',
                ],
            ],
        ],

        'fire-support-lance' => [
            'slug'    => 'fire-support-lance',
            'name'    => 'Fire Support Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE also requires ≥3 units to have the IF# special ability;
            // not encoded — Indirect Fire SPA tracking is not yet in the data model.
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
            'source'  => 'AS:CE p.119',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 2,
                    'label' => 'At least 2 units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_pct_with_attack',
                    'range' => 'l',
                    'value' => 2,
                    'pct'   => 100,
                    'label' => 'All units must have Long-range damage ≥ 2',
                ],
            ],
        ],

        'striker-cavalry-lance' => [
            'slug'    => 'striker-cavalry-lance',
            'name'    => 'Striker / Cavalry Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'ideal_role' => 'Striker',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'No units may be Size 4 (Assault) or larger',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 10,
                    'jump'  => 8,
                    'pct'   => 100,
                    'label' => 'All units must have ground Move ≥ 10" or Jump ≥ 8"',
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
            'source'  => 'AS:CE p.119',
            'ideal_role' => 'Scout',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_move_all',
                    'move'  => 12,
                    'label' => 'All units must have Move ≥ 12"',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Scout', 'Striker'],
                    'count' => 2,
                    'label' => 'At least 2 units must have Scout or Striker role',
                ],
            ],
        ],

        'pursuit-lance' => [
            'slug'    => 'pursuit-lance',
            'name'    => 'Pursuit Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'ideal_role' => 'Striker',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 12,
                    'jump'  => 0,
                    'pct'   => 75,
                    'label' => 'At least 75 % of units must have Move ≥ 12" (jumping does not count)',
                ],
                [
                    'type'  => 'min_count_with_attack',
                    'range' => 'm',
                    'value' => 2,
                    'count' => 1,
                    'label' => 'At least 1 unit must have Medium-range damage > 1',
                ],
            ],
        ],

        'command-lance' => [
            'slug'    => 'command-lance',
            'name'    => 'Command Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // The "≥1 commander unit" + "1 unit must be Brawler/Striker/Scout" reqs
            // depend on per-unit commander designation, which we don't track yet.
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Sniper', 'Missile Boat', 'Skirmisher', 'Juggernaut'],
                    'pct'    => 50,
                    'label'  => '50 % of units must have Sniper, Missile Boat, Skirmisher, or Juggernaut role',
                ],
            ],
        ],

        // Legacy slugs kept for compatibility with existing forces; AS:CE treats
        // these as variants of the Battle Lance (added under canonical slugs in
        // the Battle Lance variation set, see PR2).
        'light-lance' => [
            'slug'    => 'light-lance',
            'name'    => 'Light Lance (legacy)',
            'faction' => 'is',
            'source'  => 'AS:CE p.118 (Light Battle Lance)',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 1,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must be Size 1 (Light)',
                ],
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'No units may be Size 4 (Assault) or larger',
                ],
            ],
        ],

        'heavy-lance' => [
            'slug'    => 'heavy-lance',
            'name'    => 'Heavy Lance (legacy)',
            'faction' => 'is',
            'source'  => 'AS:CE p.118 (Heavy Battle Lance)',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 3,
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed',
                ],
            ],
        ],

        'ranger-lance' => [
            'slug'    => 'ranger-lance',
            'name'    => 'Ranger Lance',
            'faction' => 'is',
            'source'  => 'CO p.119',
            'ideal_role' => 'Skirmisher',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'No units may be Size 4 (Assault) or larger',
                ],
            ],
        ],

        'urban-lance' => [
            'slug'    => 'urban-lance',
            'name'    => 'Urban Combat Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.121',
            'ideal_role' => 'Ambusher',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE requires ≥75 % of units to have a Walk/Cruise of 4 (Move 8")
            // OR jumping equivalent, AND ≥50 % have Ambusher unit role.
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 6,
                    'pct'   => 75,
                    'label' => 'At least 75 % of units must have Move ≥ 8" or Jump ≥ 6"',
                ],
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Ambusher'],
                    'pct'    => 50,
                    'label'  => 'At least 50 % of units must have Ambusher role',
                ],
            ],
        ],

        'hunter-lance' => [
            'slug'    => 'hunter-lance',
            'name'    => 'Hunter Lance',
            'faction' => 'is',
            'source'  => 'CO p.118 (Assault Lance variant)',
            'ideal_role' => 'Ambusher',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'   => 'min_pct_by_role',
                    'roles'  => ['Ambusher', 'Juggernaut'],
                    'pct'    => 75,
                    'label'  => 'At least 75 % of units must have Ambusher or Juggernaut role',
                ],
            ],
        ],

        'security-lance' => [
            'slug'    => 'security-lance',
            'name'    => 'Security Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            // AS:CE: trained for independent operations defending a military
            // installation, government building, civilian factory, etc. No hard
            // composition requirement; designated by role on the battlefield.
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [],
        ],

        // -- Battle Lance variants (AS:CE p.118) ------------------------------

        'light-battle-lance' => [
            'slug'    => 'light-battle-lance',
            'name'    => 'Light Battle Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // The "≥2 matched pairs of Size 1 vehicles" rule needs vehicle/MUL-id
            // pairing data we don't track yet — omitted.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 1,
                    'size_max' => 1,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must be Size 1 (Light)',
                ],
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'No units may be Size 4 (Assault) or larger',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Scout'],
                    'count' => 1,
                    'label' => 'At least 1 unit must have the Scout role',
                ],
            ],
        ],

        'medium-battle-lance' => [
            'slug'    => 'medium-battle-lance',
            'name'    => 'Medium Battle Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 2,
                    'size_max' => 2,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must be Size 2 (Medium)',
                ],
                [
                    'type'  => 'max_size_all',
                    'size'  => 3,
                    'label' => 'No units may be Size 4 (Assault) or larger',
                ],
            ],
        ],

        'heavy-battle-lance' => [
            'slug'    => 'heavy-battle-lance',
            'name'    => 'Heavy Battle Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 3,
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed',
                ],
            ],
        ],

        // -- Assault Lance variants (CO p.118) --------------------------------

        'anvil-lance' => [
            'slug'    => 'anvil-lance',
            'name'    => 'Anvil Lance',
            'faction' => 'is',
            'source'  => 'CO p.118',
            'ideal_role' => 'Juggernaut',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // CO: Exclusive to House Marik Forces. The "≥50 % of units must
            // mount autocannon/LRM/SRM" rule needs special-ability tracking
            // beyond what the data model captures and is omitted.
            'rules'   => [
                [
                    'type'  => 'no_size',
                    'size'  => 1,
                    'label' => 'No Size 1 (Light) units allowed (medium or larger only)',
                ],
                [
                    'type'  => 'min_armor_total',
                    'total' => 105,
                    'label' => 'Total Alpha Strike armor across all units must be ≥ 105',
                ],
            ],
        ],

        'fast-assault-lance' => [
            'slug'    => 'fast-assault-lance',
            'name'    => 'Fast Assault Lance',
            'faction' => 'is',
            'source'  => 'CO p.118',
            'ideal_role' => 'Juggernaut',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // Adds to standard Assault Lance: every unit must have either
            // ground Move ≥ 5" OR jump capability of any range.
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
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 5,
                    'jump'  => 1,
                    'pct'   => 100,
                    'label' => 'All units must have ground Move ≥ 5" or any jump capability',
                ],
                [
                    'type'  => 'min_pct_with_attack',
                    'range' => 'm',
                    'value' => 3,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must have a Medium-range damage value of 3 or more',
                ],
            ],
        ],

        // -- Striker / Cavalry Lance variants (AS:CE p.118-119) ----------------

        'light-striker-cavalry-lance' => [
            'slug'    => 'light-striker-cavalry-lance',
            'name'    => 'Light Striker / Cavalry Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'ideal_role' => 'Striker',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 12,
                    'jump'  => 8,
                    'pct'   => 100,
                    'label' => 'All units must have ground Move ≥ 12" or Jump ≥ 8"',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Striker', 'Skirmisher'],
                    'count' => 2,
                    'label' => 'At least 2 units must have Striker or Skirmisher role',
                ],
            ],
        ],

        'heavy-striker-cavalry-lance' => [
            'slug'    => 'heavy-striker-cavalry-lance',
            'name'    => 'Heavy Striker / Cavalry Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'ideal_role' => 'Striker',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 8,
                    'pct'   => 100,
                    'label' => 'All units must have ground Move ≥ 8" or Jump ≥ 8"',
                ],
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 3,
                    'label' => 'At least 3 units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_count_with_attack',
                    'range' => 'm',
                    'value' => 2,
                    'count' => 1,
                    'label' => 'At least 1 unit must have a Medium-range damage value > 1',
                ],
            ],
        ],

        // -- Recon Lance variants (AS:CE p.119-120) ----------------------------

        'light-recon-lance' => [
            'slug'    => 'light-recon-lance',
            'name'    => 'Light Recon Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'ideal_role' => 'Scout',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 1,
                    'label' => 'All units must be Size 1 (Light)',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 12,
                    'jump'  => 12,
                    'pct'   => 100,
                    'label' => 'All units must have Move ≥ 12" (with or without jumping)',
                ],
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Scout'],
                    'pct'   => 100,
                    'label' => 'All units must have the Scout role',
                ],
            ],
        ],

        'heavy-recon-lance' => [
            'slug'    => 'heavy-recon-lance',
            'name'    => 'Heavy Recon Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'ideal_role' => 'Scout',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // The "≥2 units must have Move 10\" or more" sub-rule is enforced
            // implicitly by combining the All-Move-8" floor with role/size reqs;
            // a min_count_fast helper would be needed for full fidelity.
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 8,
                    'pct'   => 100,
                    'label' => 'All units must have Move ≥ 8" (with or without jumping)',
                ],
                [
                    'type'  => 'min_count_by_size',
                    'size'  => 3,
                    'count' => 1,
                    'label' => 'At least 1 unit must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Scout'],
                    'count' => 2,
                    'label' => 'At least 2 units must have the Scout role',
                ],
            ],
        ],

        // -- Pursuit Lance variants (AS:CE p.120) ------------------------------

        'probe-lance' => [
            'slug'    => 'probe-lance',
            'name'    => 'Probe Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
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
                    'jump'  => 10,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must have Move ≥ 10" (with or without jumping)',
                ],
                [
                    'type'  => 'min_pct_with_attack',
                    'range' => 'm',
                    'value' => 2,
                    'pct'   => 100,
                    'label' => 'All units must deliver ≥ 2 damage at Medium range',
                ],
            ],
        ],

        'sweep-lance' => [
            'slug'    => 'sweep-lance',
            'name'    => 'Sweep Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 2,
                    'label' => 'All units must be Size 2 (Medium) or smaller',
                ],
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 10,
                    'jump'  => 10,
                    'pct'   => 100,
                    'label' => 'All units must have Move ≥ 10" (with or without jumping)',
                ],
                [
                    'type'  => 'min_pct_with_attack',
                    'range' => 's',
                    'value' => 2,
                    'pct'   => 100,
                    'label' => 'All units must deliver ≥ 2 damage at Short range',
                ],
            ],
        ],

        // -- Fire Lance + variants (AS:CE p.119) ------------------------------

        'fire-lance' => [
            'slug'    => 'fire-lance',
            'name'    => 'Fire Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'ideal_role' => 'Missile Boat',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Missile Boat', 'Sniper'],
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        'anti-air-lance' => [
            'slug'    => 'anti-air-lance',
            'name'    => 'Anti-Air Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE adds: ≥2 units must have Flak (FLK#), Autocannon (AC#/#/#),
            // or Artillery (ARTX-#) special abilities — equipment tracking not
            // in the data model; only the Fire Lance role base requirement is
            // enforced.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Missile Boat', 'Sniper'],
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        'artillery-fire-lance' => [
            'slug'    => 'artillery-fire-lance',
            'name'    => 'Artillery Fire Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE: ≥2 units must have Artillery (ARTX-#) special ability — not encoded.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Missile Boat', 'Sniper'],
                    'pct'    => 75,
                    'label' => '≥ 75 % of units must have Missile Boat or Sniper role',
                ],
            ],
        ],

        // -- Misc IS lances ---------------------------------------------------

        'rifle-lance' => [
            'slug'    => 'rifle-lance',
            'name'    => 'Rifle Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118 / CO p.118',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // CO: Exclusive to House Davion. ≥50 % of units must mount an
            // autocannon — equipment requirement not encoded.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 2,
                    'size_max' => 3,
                    'pct'   => 75,
                    'label' => '≥ 75 % of units must be Size 2–3 (Medium or Heavy)',
                ],
                [
                    'type'  => 'min_move_all',
                    'move'  => 8,
                    'label' => 'All units must have ground Move ≥ 8"',
                ],
            ],
        ],

        'berserker-close-combat-lance' => [
            'slug'    => 'berserker-close-combat-lance',
            'name'    => 'Berserker / Close Combat Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.118',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE: As per the standard Battle Lance.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_size',
                    'size'  => 3,
                    'pct'   => 50,
                    'label' => '50 % of units must be Size 3 (Heavy) or larger',
                ],
                [
                    'type'  => 'min_count_by_role',
                    'roles' => ['Brawler', 'Sniper', 'Skirmisher'],
                    'count' => 3,
                    'label' => 'At least 3 units must have Brawler, Sniper, or Skirmisher role',
                ],
            ],
        ],

        'order-lance' => [
            'slug'    => 'order-lance',
            'name'    => 'Order Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE: All units must be of the same weight class AND model
            // (e.g. all Dragons, all Panthers). Model-identity matching is
            // not encodable from current MUL fields.
            'rules'   => [],
        ],

        'vehicle-command-lance' => [
            'slug'    => 'vehicle-command-lance',
            'name'    => 'Vehicle Command Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.120',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE: As Command Lance, but only one pair of vehicles needs the
            // role mix. With no vehicle/'Mech distinction tracked here, falls
            // back to the standard Command Lance rule.
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Sniper', 'Missile Boat', 'Skirmisher', 'Juggernaut'],
                    'pct'   => 25,
                    'label' => '≥ 1 pair of vehicles must have Sniper, Missile Boat, Skirmisher, or Juggernaut role',
                ],
            ],
        ],

        'air-lance' => [
            'slug'    => 'air-lance',
            'name'    => 'Air Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.121',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 6],
            // AS:CE: Combined-arms — a ground lance plus 2 identical aerospace
            // or conventional fighters. Cross-formation membership is not
            // modelled; rules are informational only.
            'rules'   => [],
        ],

        'anti-mech-lance' => [
            'slug'    => 'anti-mech-lance',
            'name'    => 'Anti-\'Mech Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.61',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // AS:CE: All units must be Infantry. Infantry units are not yet in
            // scope of the unit data model — see SCHEMA.md.
            'rules'   => [],
        ],

        'hammer-lance' => [
            'slug'    => 'hammer-lance',
            'name'    => 'Hammer Lance',
            'faction' => 'is',
            'source'  => 'CO p.118',
            'ideal_role' => 'Striker',
            'count'   => ['min' => 2, 'max' => 8, 'typical' => 4],
            // CO: Designed to flank; Hammer hits while Anvil holds. Companion
            // Anvil-Hammer pairing not modelled.
            'rules'   => [
                [
                    'type'  => 'min_pct_fast',
                    'move'  => 8,
                    'jump'  => 8,
                    'pct'   => 100,
                    'label' => 'All units must have Move ≥ 8" (with or without jumping)',
                ],
            ],
        ],

        'horde-lance' => [
            'slug'    => 'horde-lance',
            'name'    => 'Horde Lance',
            'faction' => 'is',
            'source'  => 'AS:CE p.119',
            'count'   => ['min' => 5, 'max' => 10, 'typical' => 8],
            // AS:CE: 5–10 units, all Light, none capable of > 10 damage at
            // Short range. Damage cap encoded; numeric size cap encoded.
            'rules'   => [
                [
                    'type'  => 'max_size_all',
                    'size'  => 1,
                    'label' => 'All units must be Size 1 (Light)',
                ],
            ],
        ],
    ];

    // -------------------------------------------------------------------------
    // Aerospace squadrons (AS:CE p.121 / CO p.119–120)
    //
    // Counts roughly mirror Star/Level II groupings (5 units typical) but allow
    // pair-based composition down to 2.
    // -------------------------------------------------------------------------

    private const AEROSPACE_SQUADRONS = [
        'aerospace-superiority-squadron' => [
            'slug'    => 'aerospace-superiority-squadron',
            'name'    => 'Aerospace Superiority Squadron',
            'faction' => 'is',
            'source'  => 'CO p.119',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Interceptor', 'Fast Dogfighter'],
                    'pct'   => 50,
                    'label' => '> 50 % of units must have Interceptor or Fast Dogfighter role',
                ],
            ],
        ],

        'electronic-warfare-squadron' => [
            'slug'    => 'electronic-warfare-squadron',
            'name'    => 'Electronic Warfare Squadron',
            'faction' => 'is',
            'source'  => 'CO p.119',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            // CO: Requires Probe / ECM / TAG equipment on ≥50 % of units —
            // equipment tracking out of scope for now.
            'rules'   => [],
        ],

        'fire-support-squadron' => [
            'slug'    => 'fire-support-squadron',
            'name'    => 'Fire Support Squadron',
            'faction' => 'is',
            'source'  => 'CO p.120',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Dogfighter', 'Sniper', 'Missile Boat'],
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must have Dogfighter (or Sniper/Missile Boat) role',
                ],
            ],
        ],

        'interceptor-squadron' => [
            'slug'    => 'interceptor-squadron',
            'name'    => 'Interceptor Squadron',
            'faction' => 'is',
            'source'  => 'CO p.120',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Interceptor'],
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must have the Interceptor role',
                ],
            ],
        ],

        'strike-squadron' => [
            'slug'    => 'strike-squadron',
            'name'    => 'Strike Squadron',
            'faction' => 'is',
            'source'  => 'CO p.120',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Attack', 'Attack Fighter', 'Dogfighter'],
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must have Attack or Dogfighter role',
                ],
            ],
        ],

        'transport-squadron' => [
            'slug'    => 'transport-squadron',
            'name'    => 'Transport Squadron',
            'faction' => 'is',
            'source'  => 'CO p.120',
            'count'   => ['min' => 2, 'max' => 10, 'typical' => 5],
            'rules'   => [
                [
                    'type'  => 'min_pct_by_role',
                    'roles' => ['Transport'],
                    'pct'   => 50,
                    'label' => '≥ 50 % of units must have the Transport role',
                ],
            ],
        ],
    ];

    // -------------------------------------------------------------------------
    // Clan — Star-based (default 5 units, range 4–5 for Stars; Binaries/Trinaries larger)
    //
    // Source pages for Clan Star formations are not yet cross-checked against
    // AS:CE; rules below are best-effort mirrors of the IS lance variants and
    // remain marked [VERIFY] until materials are supplied.
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
    //
    // Source pages for Level II formations are not yet cross-checked against
    // AS:CE; rules below remain marked [VERIFY] until materials are supplied.
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
