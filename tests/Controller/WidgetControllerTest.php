<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;

/**
 * @group integration
 */
class WidgetControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/widgets/working-time/2020/1');
    }

    public function testWorkingtimechartAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/widgets/working-time/2020/1');

        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('id="PaginatedWorkingTimeChartBox"', $content);
        self::assertStringContainsString('myChart = new Chart', $content);
        self::assertStringContainsString("KimaiPaginatedBoxWidget.create('#PaginatedWorkingTimeChartBox');", $content);
    }
}
