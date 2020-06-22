<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Controller;

use App\Saml\Controller\SamlController;
use App\Tests\Mocks\Saml\SamlAuthFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class SamlControllerTest extends TestCase
{
    protected function getAuth()
    {
        return (new SamlAuthFactory($this))->create();
    }

    public function testAssertionConsumerServiceAction()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the check path in your firewall.');

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $sut->assertionConsumerServiceAction();
    }

    public function testLogoutAction()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the logout path in your firewall.');

        $oauth = $this->getAuth();
        $sut = new SamlController($oauth);
        $sut->logoutAction();
    }
}
