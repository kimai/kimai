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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CurrentUserFactory extends AbstractMockFactory
{
    public function create(User $user, ?string $timezone = null): CurrentUser
    {
        return $this->getCurrentUserMock($user, $timezone);
    }

    protected function getCurrentUserMock(User $user, ?string $timezone = null)
    {
        if (null !== $timezone) {
            $pref = new UserPreference();
            $pref->setName('timezone');
            $pref->setValue($timezone);
            $user->addPreference($pref);
        }

        $mock = $this->getMockBuilder(UserRepository::class)->onlyMethods(['getUserById'])->disableOriginalConstructor()->getMock();
        $mock->expects(TestCase::atMost(1))->method('getUserById')->willReturn($user);
        /** @var UserRepository $repository */
        $repository = $mock;

        $mock = $this->getMockBuilder(UsernamePasswordToken::class)->onlyMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $mock->method('getUser')->willReturn($user);
        /** @var UsernamePasswordToken $token */
        $token = $mock;

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        return new CurrentUser($tokenStorage, $repository);
    }
}
