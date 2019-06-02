<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Ldap;

use App\Configuration\LdapConfiguration;
use App\Entity\User;
use App\Ldap\LdapManager;
use App\Ldap\LdapUserProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Ldap\LdapUserProvider
 */
class LdapUserProviderTest extends TestCase
{
    public function testDeactivatedSupportsClass()
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration(['active' => false]);

        $sut = new LdapUserProvider($manager, $config);
        self::assertFalse($sut->supportsClass(User::class));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage LDAP is deactivated, user "test" not searched
     */
    public function testDeactivatedLoadUserByUsername()
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration(['active' => false]);

        $sut = new LdapUserProvider($manager, $config);
        $sut->loadUserByUsername('test');
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Instances of "App\Entity\User" are not supported.
     */
    public function testDeactivatedRefreshUser()
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration(['active' => false]);

        $sut = new LdapUserProvider($manager, $config);
        $sut->refreshUser(new User());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage User "test" not found
     */
    public function testLoadUserByUsernameReturnsNull()
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->setMethods(['findUserByUsername'])->getMock();
        $manager->expects($this->once())->method('findUserByUsername')->willReturn(null);
        $config = new LdapConfiguration(['active' => true]);

        $sut = new LdapUserProvider($manager, $config);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->setMethods(['findUserByUsername'])->getMock();
        $manager->expects($this->once())->method('findUserByUsername')->willReturn($user);
        $config = new LdapConfiguration(['active' => true]);

        $sut = new LdapUserProvider($manager, $config);
        $actual = $sut->loadUserByUsername('test');
        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }

    public function testRefreshUserReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->setMethods(['findUserByUsername'])->getMock();
        $manager->expects($this->once())->method('findUserByUsername')->willReturn($user);
        $config = new LdapConfiguration(['active' => true]);

        $sut = new LdapUserProvider($manager, $config);
        $actual = $sut->refreshUser((new User())->setUsername('test'));
        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }
}
