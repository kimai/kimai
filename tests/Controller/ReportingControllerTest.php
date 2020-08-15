<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

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

    protected function importReportingFixture(string $role)
    {
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(50);
        $fixture->setAmountRunning(10);
        $fixture->setUser($this->getUserByRole($role));
        $fixture->setStartDate(new \DateTime());
        $this->importFixture($fixture);
    }

    public function testRedirectForDefaultReportUrl()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->request($client, '/reporting/');
        $this->assertIsRedirect($client, $this->createUrl('/reporting/week_by_user'));
        $client->followRedirect();
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
    }

    public function testUserWeekReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/reporting/week_by_user?user=4&date=12999119191');
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals(4, $option->attr('value'));
    }

    public function testUserMonthReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/reporting/month_by_user?user=4&date=12999119191');
        self::assertStringContainsString('<div class="box-body user-month-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }

    public function testMonthlyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/monthly_users_list');
        self::assertStringContainsString('<div class="box-body monthly-user-list-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }
}
