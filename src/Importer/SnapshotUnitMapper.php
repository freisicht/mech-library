<?php

declare(strict_types=1);

namespace App\Importer;

use App\Crawler\MulSnapshot;

/**
 * Builds the record UnitImporter::upsert() expects from a snapshot unit
 * plus the stats parsed from its unit page.
 *
 * The record keeps the field names of the old MUL JSON (BFMove, BFAbilities, …)
 * so the importer and its tests stay format-agnostic; `Uid` replaces `Id`.
 */
final class SnapshotUnitMapper
{
    public function __construct(
        private readonly MulSnapshot $snapshot,
        private readonly Lookups $lookups,
    ) {
    }

    /**
     * @param array<string, mixed> $unit  one entry of the snapshot's units file
     * @param array<string, mixed> $stats output of UnitPageParser::parse()
     * @return array<string, mixed>
     */
    public function map(array $unit, array $stats): array
    {
        $class   = (string) ($unit['n'] ?? '');
        $variant = (string) ($unit['m'] ?? '');
        $typeId  = (int) ($unit['t'] ?? 0);
        $role    = $this->snapshot->roles[(int) ($unit['r'] ?? 0)] ?? null;
        $damage  = $stats['damage'] ?? [];
        $move    = (string) ($stats['move'] ?? '');

        $tmm = $stats['tmm'] ?? null;
        if ($tmm === null && str_contains($move, '"')) {
            $tmm = TmmCalculator::fromBfMove($move);
        }

        return [
            'Uid'            => (string) $unit['id'],
            'Name'           => self::displayName($unit),
            'Class'          => $class,
            'Variant'        => $variant,
            'Tonnage'        => (float) ($unit['ton'] ?? 0),
            'BattleValue'    => isset($unit['bv']) ? (int) $unit['bv'] : null,
            'DateIntroduced' => isset($unit['iy']) ? (string) $unit['iy'] : null,
            'EraId'          => $this->lookup('eras', $this->snapshot->eras, $unit['ie'] ?? null),
            'ImageUrl'       => $stats['image_url'] ?? null,
            'IsPublished'    => true,
            'Type'           => $this->ref('unit_types', $this->snapshot->types, $typeId),
            'Technology'     => $this->ref('technologies', $this->snapshot->techs, $unit['te'] ?? null),
            'Role'           => $this->ref('roles', $this->snapshot->roles, $unit['r'] ?? null),
            'BFType'         => $this->bfType($typeId, (int) ($unit['st'] ?? 0)),
            'BFSize'         => $stats['size'] ?? null,
            'BFMove'         => $move,
            'TMM'            => $tmm,
            'BFArmor'        => $stats['armor'] ?? null,
            'BFStructure'    => $stats['structure'] ?? null,
            'BFThreshold'    => $stats['threshold'] ?? null,
            'BFDamageShort'     => $damage[0][0] ?? null,
            'BFDamageShortMin'  => $damage[0][1] ?? false,
            'BFDamageMedium'    => $damage[1][0] ?? null,
            'BFDamageMediumMin' => $damage[1][1] ?? false,
            'BFDamageLong'      => $damage[2][0] ?? null,
            'BFDamageLongMin'   => $damage[2][1] ?? false,
            'BFDamageExtreme'   => $damage[3][0] ?? null,
            'BFDamageExtemeMin' => $damage[3][1] ?? false,
            'BFOverheat'     => $stats['overheat'] ?? null,
            'BFPointValue'   => $stats['pv'] ?? (isset($unit['pv']) ? (int) $unit['pv'] : null),
            'BFAbilities'    => self::abilities($stats['chips'] ?? [], $role),
        ];
    }

    /** @param array<string, mixed> $unit */
    public static function displayName(array $unit): string
    {
        if (isset($unit['dt']) && is_string($unit['dt'])) {
            return $unit['dt'];
        }
        return trim(($unit['n'] ?? '') . ' ' . ($unit['m'] ?? ''));
    }

    /**
     * Turns the page's ability chips back into the comma-separated BFAbilities
     * format: "CAR 4" → "CAR4", "TUR (2/2/-,SRM2/2)" → "TUR(2/2/-,SRM2/2)".
     * The role chip at the end is dropped.
     *
     * @param list<string> $chips
     */
    public static function abilities(array $chips, ?string $role): string
    {
        $out = [];
        foreach ($chips as $chip) {
            if ($chip === $role || $chip === 'None') {
                continue;
            }
            $out[] = (string) preg_replace('#\s+#', '', $chip);
        }
        return implode(',', $out);
    }

    private function bfType(int $typeId, int $subTypeId): ?string
    {
        $sub = $this->snapshot->subTypes[$subTypeId]['name'] ?? '';
        $type = $this->snapshot->types[$typeId] ?? '';

        return match ($type) {
            'BattleMech'      => 'BM',
            'IndustrialMech'  => 'IM',
            'Battle Armor'    => 'BA',
            'ProtoMech'       => 'PM',
            'Infantry'        => 'CI',
            'Combat Vehicle', 'OmniVehicle' => 'CV',
            'Support Vehicle' => 'SV',
            'Fighter Craft'   => str_starts_with($sub, 'Conventional') ? 'CF' : 'AF',
            'Aerospace Craft' => match (true) {
                str_contains($sub, 'DropShip')  => 'DS',
                str_contains($sub, 'WarShip')   => 'WS',
                str_contains($sub, 'JumpShip')  => 'JS',
                str_contains($sub, 'Station')   => 'SS',
                default                         => 'SC',
            },
            default => null,
        };
    }

    /**
     * @param array<int, string> $names
     * @return array{Id: int, Name: string}|null
     */
    private function ref(string $table, array $names, mixed $snapshotId): ?array
    {
        $id = $this->lookup($table, $names, $snapshotId);
        return $id === null ? null : ['Id' => $id, 'Name' => $names[(int) $snapshotId]];
    }

    /** @param array<int, string> $names */
    private function lookup(string $table, array $names, mixed $snapshotId): ?int
    {
        if ($snapshotId === null || !isset($names[(int) $snapshotId])) {
            return null;
        }
        return $this->lookups->resolve($table, $names[(int) $snapshotId]);
    }
}
