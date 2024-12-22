<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

/**
 * @group integration
 */
class ReportUsersMonthControllerTest extends AbstractUsersPeriodControllerTestCase
{
    protected function getReportUrl(): string
    {
        return '/reporting/users/month';
    }

    protected function getReportExportUrl(): string
    {
        return '/reporting/users/month_export';
    }

    protected function getBoxId(): string
    {
        return 'monthly-user-list-reporting-box';
    }
}
