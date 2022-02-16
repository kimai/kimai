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
class UserMonthControllerTest extends AbstractUserPeriodControllerTest
{
    protected function getReportUrl(): string
    {
        return '/reporting/user/month';
    }

    protected function getBoxId(): string
    {
        return 'user-month-reporting-box';
    }
}
