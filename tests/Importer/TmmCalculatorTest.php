<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Importer\TmmCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TmmCalculatorTest extends TestCase
{
    /** @return array<string, array{string, int}> */
    public static function provider(): array
    {
        return [
            'immobile'          => ['0"',        0],
            'empty string'      => ['',           0],
            '1 inch'            => ['1"',         0],
            '4 inches'          => ['4"',         0],
            '5 inches'          => ['5"',         1],
            '8 inches'          => ['8"',         1],
            '9 inches'          => ['9"',         2],
            '12 inches'         => ['12"',        2],
            '13 inches'         => ['13"',        3],
            '18 inches'         => ['18"',        3],
            '19 inches'         => ['19"',        4],
            '34 inches'         => ['34"',        4],
            '35 inches'         => ['35"',        5],
            'jump suffix'       => ['12"j',       2],
            'dual walk/jump'    => ['10"/6"j',    2],
            'jump higher'       => ['4"/6"j',     1],
            'zero walk w/jump'  => ['0"/2"j',     0],
            'dual with sub'     => ['10"/10"s',   2],
            'complex dual'      => ['18"/14"j',   3],
        ];
    }

    #[DataProvider('provider')]
    public function testFromBfMove(string $bfMove, int $expectedTmm): void
    {
        self::assertSame($expectedTmm, TmmCalculator::fromBfMove($bfMove));
    }
}
