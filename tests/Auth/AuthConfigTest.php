<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\AuthConfig;
use PHPUnit\Framework\TestCase;

final class AuthConfigTest extends TestCase
{
    public function testDefaultsToMattermostWhenItIsConfigured(): void
    {
        $config = AuthConfig::resolve('', 'https://chat.example.org');
        self::assertTrue($config->isMattermost());
        self::assertSame('chat.example.org', $config->mattermostHost());
    }

    public function testDefaultsToLocalWithoutMattermost(): void
    {
        self::assertTrue(AuthConfig::resolve('', '')->isLocal());
    }

    public function testExplicitProviderWins(): void
    {
        self::assertTrue(AuthConfig::resolve(' Local ', 'https://chat.example.org')->isLocal());
    }

    public function testRejectsUnknownProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        AuthConfig::resolve('ldap', '');
    }
}
