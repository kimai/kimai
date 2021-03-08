<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Voter;

use App\Entity\User;
use App\Security\AclDecisionManager;
use App\Voter\AbstractVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @covers \App\Voter\AbstractVoter
 * @group legacy
 */
class DeprecatedAbstractVoterTest extends AbstractVoterTest
{
    protected function getVoter(string $voterClass): Voter
    {
        $accessManager = $this->getMockBuilder(AclDecisionManager::class)->disableOriginalConstructor()->getMock();
        $accessManager->method('isFullyAuthenticated')->willReturn(true);

        $class = new \ReflectionClass($voterClass);
        /** @var AbstractVoter $voter */
        $voter = $class->newInstance($accessManager, $this->getRolePermissionManager());
        self::assertInstanceOf(AbstractVoter::class, $voter);

        return $voter;
    }

    protected function assertVote(User $user, $subject, $attribute, $result)
    {
        $token = new UsernamePasswordToken($user, 'foo', 'bar', $user->getRoles());
        $sut = $this->getVoter(DeprecatedVoter::class);

        $actual = $sut->vote($token, $subject, [$attribute]);
        $this->assertEquals($result, $actual, sprintf('Failed voting "%s" for User with roles %s.', $attribute, implode(', ', $user->getRoles())));
    }

    public function testMuuu()
    {
        $userStandard = $this->getUser(1, User::ROLE_USER);
        $this->assertVote($userStandard, null, 'view_own_timesheet', true);
    }
}

class DeprecatedVoter extends AbstractVoter
{
    protected function supports($attribute, $subject)
    {
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$this->isRegisteredPermission($attribute)) {
            return false;
        }

        if (!$this->hasPermission('ROLE_USER', $attribute)) {
            return false;
        }

        /** @var User $user */
        $user = $token->getUser();

        if (!$this->hasRolePermission($user, $attribute)) {
            return false;
        }

        return $this->isFullyAuthenticated($token);
    }
}
