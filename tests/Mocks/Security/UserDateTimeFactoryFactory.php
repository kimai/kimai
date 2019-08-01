<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks\Security;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Repository\UserRepository;
use App\Security\CurrentUser;
use App\Tests\Mocks\AbstractMockFactory;
use App\Timesheet\UserDateTimeFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserDateTimeFactoryFactory extends AbstractMockFactory
{
    public function create(?string $timezone = null): UserDateTimeFactory
    {
        return new UserDateTimeFactory($this->getCurrentUserMock($timezone));
    }

    protected function getCurrentUserMock(?string $timezone = null)
    {
        $user = new User();
        if (null !== $timezone) {
            $pref = new UserPreference();
            $pref->setName('timezone');
            $pref->setValue($timezone);
            $user->addPreference($pref);
        }
        $repository = $this->getMockBuilder(UserRepository::class)->setMethods(['getUserById'])->disableOriginalConstructor()->getMock();
        $repository->expects(TestCase::exactly(1))->method('getUserById')->willReturn($user);
        $token = $this->getMockBuilder(UsernamePasswordToken::class)->setMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $token->expects(TestCase::exactly(1))->method('getUser')->willReturn($user);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        return new CurrentUser($tokenStorage, $repository);
    }
}
