<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Entity\User;
use App\Saml\SamlAuthFactory;
use App\Saml\SamlLogoutSubscriber;
use App\Saml\SamlToken;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * @covers \App\Saml\SamlLogoutSubscriber
 */
class SamlLogoutSubscriberTest extends TestCase
{
    public function testLogout(): void
    {
        $auth = $this->getMockBuilder(Auth::class)->disableOriginalConstructor()->getMock();
        $auth->expects($this->once())->method('processSLO')->willThrowException(new Error('blub'));
        $auth->expects($this->once())->method('getSLOurl')->willReturn('');

        $request = new Request();
        $token = new SamlToken(new User(), 'secured_area', []);

        $factory = $this->getMockBuilder(SamlAuthFactory::class)->disableOriginalConstructor()->getMock();
        $factory->expects($this->once())->method('create')->willReturn($auth);

        $sut = new SamlLogoutSubscriber($factory);
        $sut->logout(new LogoutEvent($request, $token));
    }

    public function testLogoutWithWrongTokenWillNotCallMethods(): void
    {
        $auth = $this->getMockBuilder(Auth::class)->disableOriginalConstructor()->getMock();
        $auth->expects($this->never())->method('processSLO');
        $auth->expects($this->never())->method('getSLOurl');

        $request = new Request();
        $token = new UsernamePasswordToken(new User(), 'test');

        $factory = $this->getMockBuilder(SamlAuthFactory::class)->disableOriginalConstructor()->getMock();
        $factory->expects($this->never())->method('create');

        $sut = new SamlLogoutSubscriber($factory);
        $sut->logout(new LogoutEvent($request, $token));
    }

    public function testLogoutWithLogoutUrl(): void
    {
        $auth = $this->getMockBuilder(Auth::class)->disableOriginalConstructor()->getMock();
        $auth->expects($this->once())->method('processSLO')->willThrowException(new Error('blub'));
        $auth->expects($this->once())->method('getSLOurl')->willReturn('/logout');
        $auth->expects($this->once())->method('logout')->willReturnCallback(function () {
            $args = \func_get_args();
            self::assertNull($args[0]);
            self::assertEquals([], $args[1]);
            self::assertEquals('tony', $args[2]);
            self::assertEquals('foo-bar', $args[3]);
        });

        $request = new Request();
        $user = new User();
        $user->setUserIdentifier('tony');
        $token = new SamlToken($user, 'secured_area', []);
        $token->setAttribute('sessionIndex', 'foo-bar');

        $factory = $this->getMockBuilder(SamlAuthFactory::class)->disableOriginalConstructor()->getMock();
        $factory->expects($this->once())->method('create')->willReturn($auth);

        $sut = new SamlLogoutSubscriber($factory);
        $sut->logout(new LogoutEvent($request, $token));
    }
}
