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
 * @coversDefaultClass \App\Controller\PluginController
 * @group integration
 */
class PluginControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/plugins/');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/plugins/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/plugins/');
        $this->assertCalloutWidgetWithMessage($client, 'You have no plugin installed yet');
        $this->assertPageActions($client, ['shop' => 'https://www.kimai.org/store/']);
    }
}
