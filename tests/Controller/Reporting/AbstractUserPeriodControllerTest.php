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
abstract class AbstractUserPeriodControllerTest extends ControllerBaseTest
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

    abstract protected function getReportUrl(): string;

    abstract protected function getBoxId(): string;

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured($this->getReportUrl());
    }

    public function getTestData(): array
    {
        return [
            [4, 'duration', 'Working hours total'],
            [4, 'rate', 'Total revenue'],
            [4, 'internalRate', 'Internal price'],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testUserPeriodReport(int $user, string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, sprintf('%s?user=%s&date=12999119191&sumType=%s', $this->getReportUrl(), $user, $dataType));
        self::assertStringContainsString(sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($user, $option->attr('value'));
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    public function testUserPeriodReportAsTeamlead(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->assertAccessIsGranted($client, sprintf('%s?date=12999119191', $this->getReportUrl()));
        self::assertStringContainsString(sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals('Working hours total', $cell->text());
    }
}
