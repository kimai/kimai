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
class AboutControllerTest extends ControllerBaseTest
{
    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/about');

        $result = $client->getCrawler()->filter('.content a.card-btn');
        self::assertCount(4, $result);

        $result = $client->getCrawler()->filter('div.card-body.card_details');
        self::assertCount(1, $result);
        self::assertStringContainsString('GNU Affero General Public License v3.0', $result->text(null, true));
    }
}
