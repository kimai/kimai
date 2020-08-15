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

    public function testWeekByUserIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/week_by_user');
    }

    public function testMonthByUserIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/month_by_user');
    }

    public function testMonthlyListIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/monthly_users_list');
    }

    public function testMonthlyUsersListIsSecureForUserRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/monthly_users_list');
    }

    public function testRedirectForDefaultReportUrl()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/reporting/');
        $this->assertIsRedirect($client, $this->createUrl('/reporting/week_by_user'));
        $client->followRedirect();
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
    }

    public function testUserWeekReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/reporting/week_by_user');
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
    }

    public function testUserMonthReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/reporting/month_by_user');
        self::assertStringContainsString('<div class="box-body user-month-reporting-box', $client->getResponse()->getContent());
    }

    public function testMonthlyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/monthly_users_list');
        self::assertStringContainsString('<div class="box-body monthly-user-list-reporting-box', $client->getResponse()->getContent());
    }
}
