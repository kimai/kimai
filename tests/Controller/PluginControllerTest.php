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
use App\Tests\Plugin\Fixtures\TestPlugin\TestPlugin;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class PluginControllerTest extends AbstractControllerBaseTestCase
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/admin/plugins/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/plugins/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/plugins/');
        $node = $client->getCrawler()->filter('div.alert.alert-warning');
        self::assertStringContainsString('You have no plugins installed yet', $node->text(null, true));
    }

    public function testIndexActionWithInstalledPlugins(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $manager = new PluginManager([new TestPlugin()]);
        self::getContainer()->set(PluginManager::class, $manager);

        $this->assertAccessIsGranted($client, '/admin/plugins/');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_plugins', 1);
    }
}
