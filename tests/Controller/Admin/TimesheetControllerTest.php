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
 * @coversDefaultClass \App\Controller\Admin\TimesheetController
 * @group integration
 */
class TimesheetControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/team/timesheet/');
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/team/timesheet/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/team/timesheet/');
        $this->assertHasDataTable($client);
    }
}
