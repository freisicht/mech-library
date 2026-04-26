<?php

declare(strict_types=1);

namespace App\Tests\Importer;

use App\Importer\BFAbilitiesParser;
use PHPUnit\Framework\TestCase;

final class BFAbilitiesParserTest extends TestCase
{
    private BFAbilitiesParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BFAbilitiesParser();
    }

    public function testEmptyStringReturnsNoTokens(): void
    {
        self::assertEmpty($this->parser->parse(''));
        self::assertEmpty($this->parser->parse(null));
    }

    public function testFlagOnlyAbility(): void
    {
        $tokens = $this->parser->parse('CASE');
        self::assertCount(1, $tokens);
        self::assertSame('CASE', $tokens[0]->tag);
        self::assertNull($tokens[0]->value);
        self::assertNull($tokens[0]->short);
    }

    public function testSingleRatingAbility(): void
    {
        $tokens = $this->parser->parse('IF1');
        self::assertCount(1, $tokens);
        self::assertSame('IF', $tokens[0]->tag);
        self::assertSame('1', $tokens[0]->value);
        self::assertNull($tokens[0]->short);
    }

    public function testPerRangeAbility(): void
    {
        $tokens = $this->parser->parse('AC2/2/-');
        self::assertCount(1, $tokens);
        self::assertSame('AC', $tokens[0]->tag);
        self::assertSame(2, $tokens[0]->short);
        self::assertSame(2, $tokens[0]->medium);
        self::assertNull($tokens[0]->long);
        self::assertSame('AC2/2/-', $tokens[0]->raw);
    }

    public function testPerRangeAllValues(): void
    {
        $tokens = $this->parser->parse('LRM1/1/1');
        self::assertCount(1, $tokens);
        self::assertSame('LRM', $tokens[0]->tag);
        self::assertSame(1, $tokens[0]->short);
        self::assertSame(1, $tokens[0]->medium);
        self::assertSame(1, $tokens[0]->long);
    }

    public function testCompositeAbility(): void
    {
        $tokens = $this->parser->parse('HT3/2');
        self::assertCount(1, $tokens);
        self::assertSame('HT', $tokens[0]->tag);
        self::assertSame('3/2', $tokens[0]->value);
        self::assertNull($tokens[0]->short);
    }

    public function testMultipleAbilities(): void
    {
        $tokens = $this->parser->parse('AC2/2/-,IF1,MHQ1');
        self::assertCount(3, $tokens);
        self::assertSame('AC', $tokens[0]->tag);
        self::assertSame('IF', $tokens[1]->tag);
        self::assertSame('MHQ', $tokens[2]->tag);
    }

    public function testRawIsPreserved(): void
    {
        $tokens = $this->parser->parse('AC2/2/-');
        self::assertSame('AC2/2/-', $tokens[0]->raw);
    }
}
