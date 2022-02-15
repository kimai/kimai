<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class ReportUsersListControllerTest extends ControllerBaseTest
{
    protected function importReportingFixture(string $role)
    {
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(50);
        $fixture->setAmountRunning(10);
        $fixture->setUser($this->getUserByRole($role));
        $fixture->setStartDate(new \DateTime());
        $this->importFixture($fixture);
    }

    public function testYearlyListIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/users/yearly');
    }

    public function testWeeklyListIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/users/weekly');
    }

    public function testMonthlyListIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/users/monthly');
    }

    public function testYearlyUsersListIsSecureForUserRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/users/yearly');
    }

    public function testWeeklyUsersListIsSecureForUserRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/users/weekly');
    }

    public function testMonthlyUsersListIsSecureForUserRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/reporting/users/monthly');
    }

    public function testYearlyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/users/yearly');
        self::assertStringContainsString('<div class="box-body yearly-user-list-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }

    public function testWeeklyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/users/weekly');
        self::assertStringContainsString('<div class="box-body weekly-user-list-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }

    public function testMonthlyUsersReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/reporting/users/monthly');
        self::assertStringContainsString('<div class="box-body monthly-user-list-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }
}
