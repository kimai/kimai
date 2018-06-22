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
 * @coversDefaultClass \App\Controller\Admin\ActivityController
 * @group integration
 */
class ActivityControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/activity/');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/admin/activity/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/activity/');
        $this->assertHasDataTable($client);
    }
}
