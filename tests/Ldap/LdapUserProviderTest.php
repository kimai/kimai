<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Entity\User;
use App\Ldap\LdapManager;
use App\Ldap\LdapUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * @covers \App\Ldap\LdapUserProvider
 */
class LdapUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifierReturnsNull(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User "test" not found');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['findUserByUsername'])->getMock();
        $manager->expects($this->once())->method('findUserByUsername')->willReturn(null);

        $sut = new LdapUserProvider($manager);
        $sut->loadUserByIdentifier('test');
    }

    public function testLoadUserByIdentifierReturnsUser(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['findUserByUsername'])->getMock();
        $manager->expects($this->once())->method('findUserByUsername')->willReturn($user);

        $sut = new LdapUserProvider($manager);
        $actual = $sut->loadUserByIdentifier('test');
        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }

    public function testRefreshUserReturnsUser(): void
    {
        $user = new User();
        $user->setUserIdentifier('foobar');
        $user->setAuth(User::AUTH_LDAP);
        self::assertTrue($user->isLdapUser());

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['updateUser'])->getMock();

        $sut = new LdapUserProvider($manager);
        $actual = $sut->refreshUser($user);

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
        self::assertTrue($user->isLdapUser());
    }

    public function testRefreshUserThrowsExceptionOnNonLdapUser(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Account "foobar" is not a registered LDAP user.');

        $user = new User();
        $user->setUserIdentifier('foobar');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['updateUser'])->getMock();

        $sut = new LdapUserProvider($manager);
        $actual = $sut->refreshUser($user);
    }
}
