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
class WizardControllerTest extends ControllerBaseTest
{
    public function testUnknownWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/wizard/foo');
        $this->assertRouteNotFound($client);
    }

    public function testIntroWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/intro');
    }

    public function testProfileWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/profile');
    }

    public function testDoneWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/done');
    }
}
