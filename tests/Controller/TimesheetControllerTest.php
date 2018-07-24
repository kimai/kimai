<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

/**
 * @coversDefaultClass \App\Controller\TimesheetController
 * @group integration
 */
class TimesheetControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/timesheet/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testCalendarAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/calendar');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testCalendarEntriesAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/calendar/entries');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
