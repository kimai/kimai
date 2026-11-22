<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class UserYearControllerTest extends AbstractUserPeriodControllerTestCase
{
    protected function getReportUrl(): string
    {
        return '/reporting/user/year';
    }

    protected function getExportUrl(): string
    {
        return '/reporting/user/year_export';
    }

    protected function getBoxId(): string
    {
        return 'user-year-reporting-box';
    }

    /**
     * This report always shows a full calendar year, even if a date in the middle
     * of the year was submitted.
     */
    public function testReportShowsFullCalendarYear(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, $this->getReportUrl() . '?date=2023-05-17');

        $columns = $client->getCrawler()->filterXPath('//div[contains(@class, "user-year-reporting-box")]//thead//th/a');

        self::assertEquals(12, $columns->count());
        self::assertEquals('January 2023', $columns->first()->text());
        self::assertEquals('December 2023', $columns->last()->text());
    }
}
