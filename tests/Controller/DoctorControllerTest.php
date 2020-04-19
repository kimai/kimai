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
class DoctorControllerTest extends ControllerBaseTest
{
    public function testDoctorIsSecure()
    {
        $this->assertUrlIsSecured('/doctor');
    }

    public function testDoctorIsSecureForRole()
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/doctor');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/doctor');

        $result = $client->getCrawler()->filter('.content .box-header');
        $this->assertEquals(7, \count($result));
    }
}
