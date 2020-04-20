<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Logout;

use App\Entity\User;
use App\Saml\Logout\SamlLogoutHandler;
use App\Saml\SamlAuth;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use OneLogin\Saml2\Error;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Saml\Logout\SamlLogoutHandler
 */
class SamlLogoutHandlerTest extends TestCase
{
    public function testLogout()
    {
        $auth = $this->getMockBuilder(SamlAuth::class)->disableOriginalConstructor()->getMock();
        $auth->expects($this->once())->method('processSLO')->willThrowException(new Error('blub'));
        $auth->expects($this->once())->method('getSLOurl')->willReturn('');

        $request = new Request();
        $response = new Response();
        $token = new SamlToken([]);

        $sut = new SamlLogoutHandler($auth);
        $sut->logout($request, $response, $token);
    }

    public function testLogoutWithLogoutUrl()
    {
        $auth = $this->getMockBuilder(SamlAuth::class)->disableOriginalConstructor()->getMock();
        $auth->expects($this->once())->method('processSLO')->willThrowException(new Error('blub'));
        $auth->expects($this->once())->method('getSLOurl')->willReturn('/logout');
        $auth->expects($this->once())->method('logout')->willReturnCallback(function () {
            $args = \func_get_args();
            self::assertEquals(null, $args[0]);
            self::assertEquals([], $args[1]);
            self::assertEquals('tony', $args[2]);
            self::assertEquals('foo-bar', $args[3]);
        });

        $request = new Request();
        $response = new Response();
        $token = new SamlToken([]);
        $token->setUser((new User())->setUsername('tony'));
        $token->setAttribute('sessionIndex', 'foo-bar');

        $sut = new SamlLogoutHandler($auth);
        $sut->logout($request, $response, $token);
    }
}
