<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Auth\User;

use App\Auth\User\SamlUserProvider;
use App\Entity\User;
use App\Repository\UserRepository;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserInterface;
use KevinPapst\AdminLTEBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @covers \App\Auth\User\SamlUserProvider
 */
class SamlUserProviderTest extends TestCase
{
    public function testLoadUserByUsernameReturnsNullThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" not found');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn(null);

        $sut = new SamlUserProvider($manager);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" not found');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willThrowException(new \Exception('foo'));

        $sut = new SamlUserProvider($manager);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameThrowsExceptionOnNonSamlUser()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "test" is registered, but not as SAML user.');

        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $sut = new SamlUserProvider($manager);
        $sut->loadUserByUsername('test');
    }

    public function testLoadUserByUsernameReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');
        $user->setAuth(User::AUTH_SAML);

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $sut = new SamlUserProvider($manager);
        $actual = $sut->loadUserByUsername('test');
        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
    }

    public function testSupportsClass()
    {
        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new SamlUserProvider($manager);

        self::assertTrue($sut->supportsClass(User::class));
        self::assertTrue($sut->supportsClass('App\Entity\User'));
        self::assertFalse($sut->supportsClass(UserInterface::class));
        self::assertFalse($sut->supportsClass(SamlUserInterface::class));
        self::assertFalse($sut->supportsClass(\FOS\UserBundle\Model\User::class));
        self::assertFalse($sut->supportsClass(TestUser::class));
    }

    public function testRefreshUserReturnsNullThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "" not found');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn(null);

        $sut = new SamlUserProvider($manager);
        $sut->refreshUser(new User());
    }

    public function testRefreshUserThrowsException()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "" not found');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willThrowException(new \Exception('foo'));

        $sut = new SamlUserProvider($manager);
        $sut->refreshUser(new User());
    }

    public function testRefreshUserThrowsExceptionOnNonSamlUser()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "foobar" is registered, but not as SAML user.');

        $user = new User();
        $user->setUsername('foobar');

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $sut = new SamlUserProvider($manager);
        $sut->refreshUser($user);
    }

    public function testRefreshUserReturnsUser()
    {
        $user = new User();
        $user->setUsername('foobar');
        $user->setAuth(User::AUTH_SAML);

        $manager = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())->method('loadUserByUsername')->willReturn($user);

        $sut = new SamlUserProvider($manager);
        $actual = $sut->refreshUser($user);

        self::assertInstanceOf(User::class, $actual);
        self::assertSame($user, $actual);
        self::assertTrue($user->isSamlUser());
    }
}
