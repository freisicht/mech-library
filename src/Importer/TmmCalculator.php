<?php

declare(strict_types=1);

namespace App\Importer;

/**
 * Derives Alpha Strike TMM from the BFMove string.
 *
 * The MUL API provides a BFTMM field but always sends 0, so we compute it
 * from the movement value using the standard AS movement-to-TMM table.
 *
 * BFMove format examples: "6"", "12"j", "10"/6"j", "0"/2"j"
 * Multiple modes are separated by "/" — we take the maximum numeric value.
 *
 * AS TMM conversion (based on running/sprint speed in inches):
 *   0-4 → 0 | 5-8 → +1 | 9-12 → +2 | 13-18 → +3 | 19-34 → +4 | 35+ → +5
 */
final class TmmCalculator
{
    public static function fromBfMove(string $bfMove): int
    {
        $max = 0;
        if (preg_match_all('/(\d+)"/', $bfMove, $m)) {
            foreach ($m[1] as $v) {
                $n = (int) $v;
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        return self::lookup($max);
    }

    private static function lookup(int $move): int
    {
        return match (true) {
            $move <= 4     => 0,
            $move <= 8     => 1,
            $move <= 12    => 2,
            $move <= 18    => 3,
            $move <= 34    => 4,
            default        => 5,
        };
    }
}
