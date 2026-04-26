<?php

declare(strict_types=1);

namespace App\Crawler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Iterates (Type, Tonnage) buckets to pull all Mechs from MUL.
 *
 * MUL returns HTTP 500 when the result set is too large (JSON length limit).
 * Bucketing by tonnage keeps every response well within the limit:
 * the largest bucket observed is ~300 units / ~300 KB.
 */
final class Pager
{
    /** Tonnage steps used on MUL for BattleMechs (5–100 t in 5-t steps). */
    private const TONNAGE_STEPS = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100];

    private LoggerInterface $logger;

    /** @param int[] $typeIds */
    public function __construct(
        private readonly MulClient $client,
        private readonly array $typeIds = [18],
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Fetch all units for all (Type, Tonnage) buckets and return
     * the merged, deduplicated list of raw unit arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(): array
    {
        $units = [];
        $seen  = [];

        foreach ($this->typeIds as $typeId) {
            foreach (self::TONNAGE_STEPS as $tonnage) {
                $this->logger->info('Pager bucket', ['type' => $typeId, 'tonnage' => $tonnage]);

                $result  = $this->client->fetchQuickList([
                    'Types'   => $typeId,
                    'MinTons' => $tonnage,
                    'MaxTons' => $tonnage,
                ]);

                $bucket = $result['Units'] ?? [];
                if (!is_array($bucket)) {
                    continue;
                }

                foreach ($bucket as $unit) {
                    if (!is_array($unit) || !isset($unit['Id'])) {
                        continue;
                    }
                    $id = (int) $unit['Id'];
                    if (!isset($seen[$id])) {
                        $seen[$id] = true;
                        $units[]   = $unit;
                    }
                }
            }
        }

        $this->logger->info('Pager complete', ['total' => count($units)]);

        return $units;
    }
}
