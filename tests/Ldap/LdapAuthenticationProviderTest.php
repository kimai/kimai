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
use App\Ldap\LdapAuthenticationProvider;
use App\Ldap\LdapManager;
use App\Ldap\LdapUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserChecker;

/**
 * @covers \App\Ldap\LdapAuthenticationProvider
 */
class LdapAuthenticationProviderTest extends TestCase
{
    public function testSupports()
    {
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration([]);
        $userProvider = new LdapUserProvider($manager);
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', 'bar', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $result = $sut->supports($token);
        self::assertTrue($result);
    }

    public function testAuthenticateWithTokenUserButEmptyPasswordThrowsException()
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The password in the token is empty. Check `erase_credentials` in your `security.yaml`');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration([]);
        $userProvider = new LdapUserProvider($manager);
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $user = (new User())->setUsername('foo')->setEnabled(true);
        $token = new UsernamePasswordToken($user, '', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $actual = $sut->authenticate($token);
    }

    public function testAuthenticateWithUsernameReturnsUser()
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The presented password cannot be empty.');

        $user = (new User())->setUsername('foo')->setEnabled(true);
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->once())->method('loadUserByUsername')->willReturn($user);
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', '', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $actual = $sut->authenticate($token);
    }

    public function testAuthenticateWithUsernameThrowsExceptionOnFailedBind()
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The presented password is invalid.');

        $user = (new User())->setUsername('foo')->setEnabled(true);
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $manager->expects($this->once())->method('bind')->willReturn(false);
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->once())->method('loadUserByUsername')->willReturn($user);
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', 'sdfsdf', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $actual = $sut->authenticate($token);
    }

    public function testAuthenticateWithUserThrowsExceptionOnFailedBind()
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The credentials were changed from another session.');

        $user = (new User())->setUsername('foo')->setEnabled(true);
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind'])->getMock();
        $manager->expects($this->once())->method('bind')->willReturn(false);
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->never())->method('loadUserByUsername');
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken($user, 'sdfsdf', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $actual = $sut->authenticate($token);
    }

    public function testAuthenticateWithUsernameReturnsUserAndBinds()
    {
        $user = (new User())->setUsername('foo')->setEnabled(true);
        $user->setPreferenceValue('ldap.dn', 'blub');
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind', 'updateUser'])->getMock();
        $manager->expects($this->once())->method('bind')->willReturn(true);
        $manager->expects($this->once())->method('updateUser')->willReturnCallback(function ($updateUser) use ($user) {
            self::assertSame($updateUser, $user);
        });
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->once())->method('loadUserByUsername')->willReturn($user);
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', 'test', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $token = $sut->authenticate($token);
        self::assertSame($token->getUser(), $user);
    }

    public function testAuthenticateWithUserReturnsUserAndBinds()
    {
        $user = (new User())->setUsername('foo')->setEnabled(true);
        $user->setPreferenceValue('ldap.dn', 'blub');
        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->onlyMethods(['bind', 'updateUser'])->getMock();
        $manager->expects($this->once())->method('bind')->willReturn(true);
        $manager->expects($this->once())->method('updateUser')->willReturnCallback(function ($updateUser) use ($user) {
            self::assertSame($updateUser, $user);
        });
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->never())->method('loadUserByUsername');
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken($user, 'test', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $token = $sut->authenticate($token);
        self::assertSame($token->getUser(), $user);
    }

    public function testAuthenticateThrowsExceptionOnLdapNotFound()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('blub foo bar');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->once())->method('loadUserByUsername')->willThrowException(new UsernameNotFoundException('blub foo bar'));
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', 'test', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $sut->authenticate($token);
    }

    public function testAuthenticateThrowsExceptionOnLdapDown()
    {
        $this->expectException(AuthenticationServiceException::class);
        $this->expectExceptionMessage('server away');
        $this->expectExceptionCode('1234');

        $manager = $this->getMockBuilder(LdapManager::class)->disableOriginalConstructor()->getMock();
        $config = new LdapConfiguration([]);
        $userProvider = $this->getMockBuilder(LdapUserProvider::class)->disableOriginalConstructor()->onlyMethods(['loadUserByUsername'])->getMock();
        $userProvider->expects($this->once())->method('loadUserByUsername')->willThrowException(new \Exception('server away', 1234));
        $providerKey = 'secured_area';
        $userChecker = new UserChecker();

        $token = new UsernamePasswordToken('foo', 'test', $providerKey);

        $sut = new LdapAuthenticationProvider($userChecker, $providerKey, $userProvider, $manager, $config, false);
        $sut->authenticate($token);
    }
}
