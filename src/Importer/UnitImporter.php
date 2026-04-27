<?php

declare(strict_types=1);

namespace App\Importer;

use App\Model\Eras;
use App\Model\ErasQuery;
use App\Model\Roles;
use App\Model\RolesQuery;
use App\Model\Technologies;
use App\Model\TechnologiesQuery;
use App\Model\UnitAbilities;
use App\Model\UnitAbilitiesQuery;
use App\Model\Units;
use App\Model\UnitsQuery;
use App\Model\UnitTypes;
use App\Model\UnitTypesQuery;
use Propel\Runtime\Propel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class UnitImporter
{
    private LoggerInterface $logger;
    private BFAbilitiesParser $abilityParser;

    /** @var array<int, bool> */
    private array $unitTypeCache = [];
    /** @var array<int, bool> */
    private array $techCache = [];
    /** @var array<int, bool> */
    private array $roleCache = [];
    /** @var array<int, bool> */
    private array $eraCache = [];

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger        = $logger ?? new NullLogger();
        $this->abilityParser = new BFAbilitiesParser();
    }

    /**
     * Upsert a single raw MUL unit array into the database.
     *
     * Returns true if the row was created or modified, false if unchanged.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(array $data): bool
    {
        $mulId = isset($data['Id']) ? (int) $data['Id'] : null;
        if ($mulId === null) {
            $this->logger->warning('Skipping unit without Id', ['data' => $data]);
            return false;
        }

        $this->ensureLookups($data);

        $unit = UnitsQuery::create()->findOneByMulId($mulId) ?? new Units();
        $isNew = $unit->isNew();

        $this->mapFields($unit, $data);

        if (!$isNew && !$unit->isModified()) {
            return false;
        }

        $unit->save();

        // Rebuild abilities: delete all existing rows, re-insert from raw string.
        // Simpler than diffing; abilities string rarely changes.
        UnitAbilitiesQuery::create()->filterByUnitId($unit->getId())->delete();
        $this->insertAbilities($unit->getId(), (string) ($data['BFAbilities'] ?? ''));

        $this->logger->debug('Upserted unit', ['mul_id' => $mulId, 'new' => $isNew]);

        return true;
    }

    /**
     * Import all JSON files from the given directory.
     * Returns [inserted, updated, skipped] counts.
     *
     * @return array{inserted: int, updated: int, skipped: int}
     */
    public function importDirectory(string $dir): array
    {
        $counts = ['inserted' => 0, 'updated' => 0, 'skipped' => 0];

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = file_get_contents($file);
            if ($raw === false) {
                $this->logger->warning('Cannot read file', ['file' => $file]);
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $this->logger->warning('Invalid JSON', ['file' => $file]);
                continue;
            }

            $units = $decoded['Units'] ?? [$decoded];
            if (!is_array($units)) {
                continue;
            }

            $con = Propel::getConnection();
            $con->beginTransaction();
            try {
                foreach ($units as $unit) {
                    if (!is_array($unit)) {
                        continue;
                    }
                    $mulId  = isset($unit['Id']) ? (int) $unit['Id'] : null;
                    $exists = $mulId !== null
                        && UnitsQuery::create()->findOneByMulId($mulId) !== null;

                    $changed = $this->upsert($unit);

                    if (!$changed) {
                        $counts['skipped']++;
                    } elseif ($exists) {
                        $counts['updated']++;
                    } else {
                        $counts['inserted']++;
                    }
                }
                $con->commit();
            } catch (\Throwable $e) {
                $con->rollBack();
                throw $e;
            }
        }

        return $counts;
    }

    /** @param array<string, mixed> $data */
    private function mapFields(Units $unit, array $data): void
    {
        $unit->setMulId((int) ($data['Id'] ?? 0));
        $unit->setName((string) ($data['Name'] ?? ''));
        $unit->setClass((string) ($data['Class'] ?? ''));
        $unit->setVariant((string) ($data['Variant'] ?? ''));
        $unit->setTonnage((int) ($data['Tonnage'] ?? 0));
        $unit->setBattleValue(isset($data['BattleValue']) ? (int) $data['BattleValue'] : null);
        $unit->setCost(isset($data['Cost']) ? (string) (int) $data['Cost'] : null);
        $unit->setRules((string) ($data['Rules'] ?? ''));
        $unit->setDateIntroduced(isset($data['DateIntroduced']) ? (string) $data['DateIntroduced'] : null);
        $unit->setEraId(isset($data['EraId']) ? (int) $data['EraId'] : null);
        $unit->setEraStart(isset($data['EraStart']) ? (int) $data['EraStart'] : null);
        $unit->setImageUrl(isset($data['ImageUrl']) ? (string) $data['ImageUrl'] : null);
        $unit->setIsPublished((bool) ($data['IsPublished'] ?? true));
        $unit->setMulRelease(isset($data['Release']) ? (string) $data['Release'] : null);

        $typeId = isset($data['Type']['Id']) ? (int) $data['Type']['Id'] : null;
        $unit->setTypeId($typeId);

        $techId = isset($data['Technology']['Id']) ? (int) $data['Technology']['Id'] : null;
        $unit->setTechnologyId($techId);

        $roleId = isset($data['Role']['Id']) ? (int) $data['Role']['Id'] : null;
        $unit->setRoleId($roleId);

        $unit->setBfType(isset($data['BFType']) ? (string) $data['BFType'] : null);
        $unit->setBfSize(isset($data['BFSize']) ? (int) $data['BFSize'] : null);
        $bfMove = isset($data['BFMove']) ? (string) $data['BFMove'] : null;
        $unit->setBfMove($bfMove);
        // MUL always sends BFTMM=0; derive it from BFMove instead.
        $unit->setBfTmm(TmmCalculator::fromBfMove($bfMove ?? ''));
        $unit->setBfArmor(isset($data['BFArmor']) ? (int) $data['BFArmor'] : null);
        $unit->setBfStructure(isset($data['BFStructure']) ? (int) $data['BFStructure'] : null);
        $unit->setBfThreshold(isset($data['BFThreshold']) ? (int) $data['BFThreshold'] : null);
        $unit->setBfDamageShort(isset($data['BFDamageShort']) ? (int) $data['BFDamageShort'] : null);
        $unit->setBfDamageShortMin((bool) ($data['BFDamageShortMin'] ?? false));
        $unit->setBfDamageMedium(isset($data['BFDamageMedium']) ? (int) $data['BFDamageMedium'] : null);
        $unit->setBfDamageMediumMin((bool) ($data['BFDamageMediumMin'] ?? false));
        $unit->setBfDamageLong(isset($data['BFDamageLong']) ? (int) $data['BFDamageLong'] : null);
        $unit->setBfDamageLongMin((bool) ($data['BFDamageLongMin'] ?? false));
        $unit->setBfDamageExtreme(isset($data['BFDamageExtreme']) ? (int) $data['BFDamageExtreme'] : null);
        // Mirror API typo: field is "BFDamageExtemeMin" (missing 'r')
        $unit->setBfDamageExtremeMin((bool) ($data['BFDamageExtemeMin'] ?? false));
        $unit->setBfOverheat(isset($data['BFOverheat']) ? (int) $data['BFOverheat'] : null);
        $unit->setBfPointValue(isset($data['BFPointValue']) ? (int) $data['BFPointValue'] : null);
        $unit->setBfAbilities(isset($data['BFAbilities']) ? (string) $data['BFAbilities'] : null);
    }

    /** @param array<string, mixed> $data */
    private function ensureLookups(array $data): void
    {
        if (isset($data['Type']['Id'], $data['Type']['Name'])) {
            $id   = (int) $data['Type']['Id'];
            $name = (string) $data['Type']['Name'];
            if (!isset($this->unitTypeCache[$id])) {
                $this->upsertLookup(UnitTypesQuery::class, UnitTypes::class, $id, $name);
                $this->unitTypeCache[$id] = true;
            }
        }

        if (isset($data['Technology']['Id'], $data['Technology']['Name'])) {
            $id   = (int) $data['Technology']['Id'];
            $name = (string) $data['Technology']['Name'];
            if (!isset($this->techCache[$id])) {
                $this->upsertLookup(TechnologiesQuery::class, Technologies::class, $id, $name);
                $this->techCache[$id] = true;
            }
        }

        if (isset($data['Role']['Id'], $data['Role']['Name'])) {
            $id   = (int) $data['Role']['Id'];
            $name = (string) $data['Role']['Name'];
            if (!isset($this->roleCache[$id])) {
                $this->upsertLookup(RolesQuery::class, Roles::class, $id, $name);
                $this->roleCache[$id] = true;
            }
        }

        if (isset($data['EraId'])) {
            $id = (int) $data['EraId'];
            if (!isset($this->eraCache[$id])) {
                // Only create a stub era if one does not already exist;
                // mul:seed-eras will fill in the name later.
                if (ErasQuery::create()->findPk($id) === null) {
                    $era = new Eras();
                    $era->setId($id);
                    $era->setName("Era #{$id}");
                    $era->save();
                }
                $this->eraCache[$id] = true;
            }
        }
    }

    /**
     * @param class-string $queryClass
     * @param class-string $modelClass
     */
    private function upsertLookup(string $queryClass, string $modelClass, int $id, string $name): void
    {
        $existing = $queryClass::create()->findPk($id);
        if ($existing !== null) {
            return;
        }
        /** @var UnitTypes|Technologies|Roles $obj */
        $obj = new $modelClass();
        $obj->setId($id);
        $obj->setName($name);
        $obj->save();
    }

    private function insertAbilities(int $unitId, string $raw): void
    {
        if ($raw === '') {
            return;
        }

        foreach ($this->abilityParser->parse($raw) as $token) {
            $ability = new UnitAbilities();
            $ability->setUnitId($unitId);
            $ability->setTag($token->tag);
            $ability->setShort($token->short);
            $ability->setMedium($token->medium);
            $ability->setLong($token->long);
            $ability->setValue($token->value);
            $ability->setRaw($token->raw);
            $ability->save();
        }
    }
}
