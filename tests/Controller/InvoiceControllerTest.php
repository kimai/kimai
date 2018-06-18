<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;

/**
 * @coversDefaultClass \App\Controller\InvoiceController
 * @group integration
 */
class InvoiceControllerTest extends ControllerBaseTest
{

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/invoice/');
    }

    public function testIndexAction()
    {
        $this->markTestSkipped('create invoice template before this test case');
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/invoice/');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertMainContentClass($client, 'dashboard');
    }
}
