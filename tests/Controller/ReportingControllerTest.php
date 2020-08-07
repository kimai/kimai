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
 * @group integration
 */
class ReportingControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/reporting');
    }

    public function testMonthlyListIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/monthly_users_list');
    }

    public function testMonthlyUsersListIsSecureForUserRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/monthly_users_list');
    }

    public function testDefaultUsersMonthReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/reporting/');
        self::assertStringContainsString('<div class="box-body user-month-reporting-box', $client->getResponse()->getContent());
    }

    public function testMonthlyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/monthly_users_list');
        self::assertStringContainsString('<div class="box-body monthly-user-list-reporting-box', $client->getResponse()->getContent());
    }
}
