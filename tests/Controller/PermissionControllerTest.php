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
class PermissionControllerTest extends ControllerBaseTest
{
    public function testPermissionsIsSecure()
    {
        $this->assertUrlIsSecured('/admin/permissions');
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/admin/permissions');
    }

    public function testPermissions()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/permissions');
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_user_admin_permissions', 83);
        $this->assertPageActions($client, [
            'back' => $this->createUrl('/admin/user/'),
            'roles modal-ajax-form' => $this->createUrl('/admin/permissions/roles'),
            'help' => 'https://www.kimai.org/documentation/permissions.html'
        ]);
    }

    // FIXME further action tests
}
