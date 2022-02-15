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
class ReportByUserControllerTest extends ControllerBaseTest
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

    public function testWeekByUserIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/user/week');
    }

    public function testMonthByUserIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/user/month');
    }

    public function testUserWeekReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/reporting/user/week?user=4&date=12999119191');
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals(4, $option->attr('value'));
    }

    public function testUserMonthReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/reporting/user/month?user=4&date=12999119191');
        self::assertStringContainsString('<div class="box-body user-month-reporting-box', $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
    }
}
