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
    public function testClauseWithoutGroupsOnlyAllowsPublicArmies(): void
    {
        $vis = new ArmyVisibility(new CurrentUser());
        $c   = $vis->clause('a');
        self::assertSame('(a.required_group_id IS NULL)', $c['sql']);
        self::assertSame([], $c['params']);
    }

    public function testClauseWithGroupsExpandsInList(): void
    {
        $current = $this->currentUserWithGroups(['ch_alpha', 'ch_beta']);
        $vis = new ArmyVisibility($current);
        $c   = $vis->clause('armies');
        self::assertStringContainsString('armies.required_group_id IS NULL OR armies.required_group_id IN', $c['sql']);
        self::assertSame(['ch_alpha', 'ch_beta'], array_values($c['params']));
    }

    public function testCanAccessArmyAllowsNullGroup(): void
    {
        $vis = new ArmyVisibility(new CurrentUser());
        self::assertTrue($vis->canAccessArmy(null));
        self::assertTrue($vis->canAccessArmy(''));
    }

    public function testCanAccessArmyChecksMembership(): void
    {
        $current = $this->currentUserWithGroups(['ch_alpha']);
        $vis = new ArmyVisibility($current);
        self::assertTrue($vis->canAccessArmy('ch_alpha'));
        self::assertFalse($vis->canAccessArmy('ch_other'));
    }

    /** @param list<string> $groups */
    private function currentUserWithGroups(array $groups): CurrentUser
    {
        $user = new Users();
        $user->setProvider('local');
        $user->setExternalId('tester');
        $user->setUsername('tester');
        $user->setGroupsJson(json_encode($groups, JSON_THROW_ON_ERROR));

        $users = new UserRepository();
        $current = new CurrentUser();
        $current->set($user, $users);
        return $current;
    }
}
