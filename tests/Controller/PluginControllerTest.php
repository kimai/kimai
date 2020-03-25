<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Plugin\PluginManager;
use App\Tests\Plugin\Fixtures\TestPlugin;

/**
 * @group integration
 */
class PluginControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/admin/plugins/');
    }

    public function testIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/plugins/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/plugins/');
        $this->assertCalloutWidgetWithMessage($client, 'You have no plugins installed yet');
        $this->assertPageActions($client, ['shop' => 'https://www.kimai.org/store/', 'help' => 'https://www.kimai.org/documentation/plugins.html']);
    }

    public function testIndexActionWithInstalledPlugins()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        /** @var PluginManager $manager */
        $manager = self::$container->get(PluginManager::class);
        $manager->addPlugin(new TestPlugin());

        $this->assertAccessIsGranted($client, '/admin/plugins/');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_plugins', 1);
    }
}
