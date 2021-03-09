<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Reporting;

use App\Entity\User;
use App\Tests\Controller\ControllerBaseTest;

/**
 * @group integration
 */
class ProjectViewControllerTest extends ControllerBaseTest
{
    public function testProjectViewIsSecure()
    {
        $this->assertUrlIsSecured('/reporting/project_view');
    }

    public function testProjectViewReport()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/reporting/project_view');
        self::assertStringContainsString('<div class="box-body project-view-reporting-box', $client->getResponse()->getContent());
    }
}
