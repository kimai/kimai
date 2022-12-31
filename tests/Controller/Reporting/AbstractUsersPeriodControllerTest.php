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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group integration
 */
abstract class AbstractUsersPeriodControllerTest extends ControllerBaseTest
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

    abstract protected function getReportExportUrl(): string;

    abstract protected function getBoxId(): string;

    public function testIsSecure()
    {
        $this->assertUrlIsSecured($this->getReportUrl());
    }

    public function getTestData(): array
    {
        return [
            ['duration', 'Working hours total'],
            ['rate', 'Total revenue'],
            ['internalRate', 'Internal price'],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testUsersPeriodReport(string $dataType, string $title)
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, sprintf('%s?date=12999119191&sumType=%s', $this->getReportUrl(), $dataType));
        self::assertStringContainsString(sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    /**
     * @dataProvider getTestData
     */
    public function testUsersPeriodReportAsTeamlead(string $dataType, string $title)
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, sprintf('%s?date=12999119191&sumType=%s', $this->getReportUrl(), $dataType));
        self::assertStringContainsString(sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    /**
     * @dataProvider getTestData
     */
    public function testUsersPeriodReportExport(string $dataType, string $title)
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->request($client, sprintf('%s?date=12999119191&sumType=%s', $this->getReportExportUrl(), $dataType));
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=kimai-export-users-', $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }
}
