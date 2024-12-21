<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

/**
 * @group integration
 */
class DashboardControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/dashboard/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/dashboard/');
        self::assertTrue($client->getResponse()->isSuccessful());
        $this->assertMainContentClass($client, 'dashboard');
        self::assertEquals(1, $client->getCrawler()->filter('div#PaginatedWorkingTimeChartBox canvas')->count());
    }
}
