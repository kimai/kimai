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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group integration
 */
abstract class AbstractUserPeriodControllerTestCase extends AbstractControllerBaseTestCase
{
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

    abstract protected function getExportUrl(): string;

    abstract protected function getBoxId(): string;

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured($this->getReportUrl());
    }

    public static function getTestData(): array
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
        $this->assertAccessIsGranted($client, \sprintf('%s?user=%s&date=12999119191&sumType=%s', $this->getReportUrl(), $user, $dataType));
        self::assertStringContainsString(\sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $option = $client->getCrawler()->filterXPath("//select[@id='user']/option[@selected]");
        self::assertEquals($user, $option->attr('value'));
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals($title, $cell->text());
    }

    /**
     * @dataProvider getTestData
     */
    public function testUserPeriodReportExport(int $user, string $dataType, string $title): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->importReportingFixture(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, \sprintf('%s?user=%s&date=2023-12-23&sumType=%s', $this->getExportUrl(), $user, $dataType));

        $response = $client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(BinaryFileResponse::class, $response);

        // temporary file will be deleted!
        $file = $response->getFile();
        self::assertFileDoesNotExist($response->getFile());

        $disposition = $response->headers->get('Content-Disposition');
        self::assertIsString($disposition);
        self::assertNotEmpty($disposition);

        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=kimai-export-user-', $disposition);
    }

    public function testUserPeriodReportAsTeamlead(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importReportingFixture(User::ROLE_USER);
        $this->assertAccessIsGranted($client, \sprintf('%s?date=12999119191', $this->getReportUrl()));
        self::assertStringContainsString(\sprintf('<div class="card-body %s', $this->getBoxId()), $client->getResponse()->getContent());
        $select = $client->getCrawler()->filterXPath("//select[@id='user']");
        self::assertEquals(0, $select->count());
        $cell = $client->getCrawler()->filterXPath("//th[contains(@class, 'reportDataTypeTitle')]");
        self::assertEquals('Working hours total', $cell->text());
    }
}
