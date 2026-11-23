<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
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

    /**
     * The dashboard is changed through the API, the page only renders the target URLs.
     */
    public function testActionsPointToTheApi(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/dashboard/edit/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $reset = $client->getCrawler()->filter('a.api-link[data-href="/api/dashboard/widgets"]');
        // the action is rendered twice, as a button and inside the responsive dropdown
        self::assertGreaterThan(0, $reset->count(), 'Could not find the reset action');
        self::assertEquals('DELETE', $reset->first()->attr('data-method'));

        $add = $client->getCrawler()->filter('a.api-link[data-href^="/api/dashboard/widgets/"]');
        self::assertGreaterThan(0, $add->count(), 'Could not find any "add widget" action');
        self::assertEquals('POST', $add->first()->attr('data-method'));
    }
}
