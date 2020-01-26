<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Auth\User;

use App\Auth\User\DoctrineUserProvider;
use App\Entity\User;
use FOS\UserBundle\Model\UserManager;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use KevinPapst\AdminLTEBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @covers \App\Auth\User\DoctrineUserProvider
 */
class DoctrineUserProviderTest extends TestCase
{
    public function testLoadUserByUsernameReturnsNullThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" not found.');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserByUsernameOrEmail')->willReturn(null);

        $sut = new DoctrineUserProvider($manager);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameReturnsNullThrowsExceptionOnNonInternalUser()
    {
        $user = new User();
        $user->setUsername('test');
        $user->setAuth(User::AUTH_SAML);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" is registered, but not as internal user.');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserByUsernameOrEmail')->willReturn($user);

        $sut = new DoctrineUserProvider($manager);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserByUsernameOrEmail')->willReturn($user);

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->loadUserByUsername('test');

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }

    public function testSupportsClass()
    {
        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();

        $sut = new DoctrineUserProvider($manager);

        self::assertTrue($sut->supportsClass(User::class));
        self::assertTrue($sut->supportsClass('App\Entity\User'));
        self::assertFalse($sut->supportsClass(UserInterface::class));
        self::assertFalse($sut->supportsClass(SamlUserInterface::class));
        self::assertFalse($sut->supportsClass(\FOS\UserBundle\Model\User::class));
        self::assertFalse($sut->supportsClass(TestUser::class));
    }

    public function testRefreshUserThrowsExceptionOnUnsupportedUserClass()
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Expected an instance of App\Entity\User, but got "App\Tests\Auth\User\TestUser".');

        $user = new TestUser();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->refreshUser($user);
    }

    public function testRefreshUserThrowsExceptionOnNonFoundUser()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User with ID "" could not be reloaded');

        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserBy')->willReturn(null);

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->refreshUser($user);
    }

    public function testRefreshUserThrowsExceptionOnNonInternalUser()
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('User "foobar" is registered, but not as internal user.');

        $user = new User();
        $user->setUsername('foobar');
        $user->setAuth(User::AUTH_SAML);

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserBy')->willReturn($user);

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->refreshUser($user);
    }

    public function testRefreshUserThrowsNoExceptionOnLdapUser()
    {
        $user = new User();
        $user->setUsername('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserBy')->willReturn($user);

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->refreshUser($user);

        self::assertSame($user, $actual);
    }

    public function testRefreshUserReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('findUserBy')->willReturn($user);

        $sut = new DoctrineUserProvider($manager);
        $actual = $sut->refreshUser($user);

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
        self::assertTrue($user->isInternalUser());
    }
}

class TestUser extends \FOS\UserBundle\Model\User
{
}
