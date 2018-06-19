<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;

/**
 * @coversDefaultClass \App\Controller\Admin\CustomerController
 * @group integration
 */
class CustomerControllerTest extends ControllerBaseTest
{

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/customer/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/customer/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/customer/');
        $this->assertHasDataTable($client);
    }
}
