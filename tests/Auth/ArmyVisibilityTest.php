<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\ArmyVisibility;
use App\Auth\CurrentUser;
use App\Auth\UserRepository;
use App\Model\Users;
use PHPUnit\Framework\TestCase;

final class ArmyVisibilityTest extends TestCase
{
    public function testClauseWithoutChannelsOnlyAllowsPublicArmies(): void
    {
        $vis = new ArmyVisibility(new CurrentUser());
        $c   = $vis->clause('a');
        self::assertSame('(a.required_mm_channel_id IS NULL)', $c['sql']);
        self::assertSame([], $c['params']);
    }

    public function testClauseWithChannelsExpandsInList(): void
    {
        $current = $this->currentUserWithChannels(['ch_alpha', 'ch_beta']);
        $vis = new ArmyVisibility($current);
        $c   = $vis->clause('armies');
        self::assertStringContainsString('armies.required_mm_channel_id IS NULL OR armies.required_mm_channel_id IN', $c['sql']);
        self::assertSame(['ch_alpha', 'ch_beta'], array_values($c['params']));
    }

    public function testCanAccessArmyAllowsNullChannel(): void
    {
        $vis = new ArmyVisibility(new CurrentUser());
        self::assertTrue($vis->canAccessArmy(null));
        self::assertTrue($vis->canAccessArmy(''));
    }

    public function testCanAccessArmyChecksMembership(): void
    {
        $current = $this->currentUserWithChannels(['ch_alpha']);
        $vis = new ArmyVisibility($current);
        self::assertTrue($vis->canAccessArmy('ch_alpha'));
        self::assertFalse($vis->canAccessArmy('ch_other'));
    }

    /** @param list<string> $channels */
    private function currentUserWithChannels(array $channels): CurrentUser
    {
        $user = new Users();
        $user->setMmUserId('mm_test');
        $user->setUsername('tester');
        $user->setChannelsJson(json_encode($channels, JSON_THROW_ON_ERROR));

        $users = new UserRepository();
        $current = new CurrentUser();
        $current->set($user, $users);
        return $current;
    }
}
