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
class ReportingControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/reporting');
    }

    public function testRedirectForDefaultReportUrl()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/reporting/');
        $this->assertIsRedirect($client, $this->createUrl('/reporting/user/week'));
        $client->followRedirect();
        self::assertStringContainsString('<div class="box-body user-week-reporting-box', $client->getResponse()->getContent());
    }
}
