<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Tests\Controller\AbstractControllerBaseTestCase;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\TeamRestrictedTimesheetTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Group('integration')]
abstract class AbstractUsersPeriodControllerTestCase extends AbstractControllerBaseTestCase
{
    use TeamRestrictedTimesheetTrait;

    protected function importReportingFixture(string $role): void
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

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured($this->getReportUrl());
    }

    public static function getTestData(): array
    {
        return [
            ['duration', 'Total'],
            ['rate', 'Total revenue'],
            ['internalRate', 'Internal price'],
        ];
    }

    #[DataProvider('getTestData')]
    public function testUsersPeriodReport(string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, \sprintf('%s?date=12999119191&sumType=%s', $this->getReportUrl(), $dataType));
        self::assertStringContainsString(\sprintf('<div class="card-body p-0 %s', $this->getBoxId()), $client->getResponse()->getContent());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    #[DataProvider('getTestData')]
    public function testUsersPeriodReportAsTeamlead(string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importReportingFixture(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, \sprintf('%s?date=12999119191&sumType=%s', $this->getReportUrl(), $dataType));
        self::assertStringContainsString(\sprintf('<div class="card-body p-0 %s', $this->getBoxId()), $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    #[DataProvider('getTestData')]
    public function testUsersPeriodReportExport(string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->request($client, \sprintf('%s?date=12999119191&sumType=%s', $this->getReportExportUrl(), $dataType));
        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=kimai-export-users-', $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    /**
     * The totals of a team member do not include the records, which the teamlead cannot see
     * in the report of that user: the ones for projects and activities of foreign teams.
     */
    public function testUsersPeriodReportHidesRecordsOfForeignTeams(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();

        $this->assertAccessIsGranted($client, \sprintf('%s?date=2020-05-13', $this->getReportUrl()));

        $total = $client->getCrawler()->filterXPath(\sprintf("//td/a[contains(@href, 'user=%s')]", $member->getId()));
        self::assertGreaterThanOrEqual(1, $total->count());
        self::assertEquals('1:00', trim($total->first()->text()));

        // users always see all of their own records
        $total = $client->getCrawler()->filterXPath(\sprintf("//td/a[contains(@href, 'user=%s')]", $teamlead->getId()));
        self::assertGreaterThanOrEqual(1, $total->count());
        self::assertEquals('1:00', trim($total->first()->text()));
    }

    /**
     * Control for testUsersPeriodReportHidesRecordsOfForeignTeams(): users with the
     * permission to see all data are not limited.
     */
    public function testUsersPeriodReportShowsRecordsOfForeignTeamsToAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        [$teamlead, $member] = $this->createTeamRestrictedTimesheets();

        $this->assertAccessIsGranted($client, \sprintf('%s?date=2020-05-13', $this->getReportUrl()));

        $total = $client->getCrawler()->filterXPath(\sprintf("//td/a[contains(@href, 'user=%s')]", $member->getId()));
        self::assertGreaterThanOrEqual(1, $total->count());
        self::assertEquals('3:00', trim($total->first()->text()));
    }
}
