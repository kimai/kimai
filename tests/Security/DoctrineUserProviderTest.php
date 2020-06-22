<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\DoctrineUserProvider;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use KevinPapst\AdminLTEBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @covers \App\Security\DoctrineUserProvider
 */
class DoctrineUserProviderTest extends TestCase
{
    public function testLoadUserByUsernameReturnsNullThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" not found.');

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('loadUserByUsername')->willReturn(null);

        $sut = new DoctrineUserProvider($repository);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $sut = new DoctrineUserProvider($repository);
        $actual = $sut->loadUserByUsername('test');

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }

    public function testSupportsClass()
    {
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new DoctrineUserProvider($repository);

        self::assertTrue($sut->supportsClass(User::class));
        self::assertTrue($sut->supportsClass('App\Entity\User'));
        self::assertFalse($sut->supportsClass(UserInterface::class));
        self::assertFalse($sut->supportsClass(SamlUserInterface::class));
        self::assertFalse($sut->supportsClass(\FOS\UserBundle\Model\User::class));
        self::assertFalse($sut->supportsClass(TestUserEntity::class));
    }

    public function testRefreshUserThrowsExceptionOnUnsupportedUserClass()
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Expected an instance of App\Entity\User, but got "App\Tests\Security\TestUserEntity".');

        $user = new TestUserEntity();
        $user->setUsername('foobar');

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new DoctrineUserProvider($repository);
        $actual = $sut->refreshUser($user);
    }

    public function testRefreshUserThrowsExceptionOnNonFoundUser()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User with ID "" could not be reloaded');

        $user = new User();
        $user->setUsername('foobar');

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('getUserById')->willReturn(null);

        $sut = new DoctrineUserProvider($repository);
        $actual = $sut->refreshUser($user);
    }

    public function testRefreshUserThrowsNoExceptionOnLdapUser()
    {
        $user = new User();
        $user->setUsername('foobar');
        $user->setAuth(User::AUTH_LDAP);

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('getUserById')->willReturn($user);

        $sut = new DoctrineUserProvider($repository);
        $actual = $sut->refreshUser($user);

        self::assertSame($user, $actual);
    }

    public function testRefreshUserReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('getUserById')->willReturn($user);

        $sut = new DoctrineUserProvider($repository);
        $actual = $sut->refreshUser($user);

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
        self::assertTrue($user->isInternalUser());
    }
}
