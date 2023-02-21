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
class HelpControllerTest extends ControllerBaseTest
{
    public function testHelpLocalesAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/help/locales');
        $this->assertDataTableRowCount($client, 'datatable_help_locales', 25); // @see services_test.yaml
    }
}
