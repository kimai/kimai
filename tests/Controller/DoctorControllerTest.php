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
class DoctorControllerTest extends AbstractControllerBaseTestCase
{
    public function testDoctorIsSecure(): void
    {
        $this->assertUrlIsSecured('/doctor');
    }

    public function testDoctorIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/doctor');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/doctor');

        $result = $client->getCrawler()->filter('.content .accordion-header');
        $counter = \count($result);
        // this can contain a warning box, that a new release is available
        self::assertTrue($counter === 6 || $counter === 5);
    }

    public function testFlushLogWithInvalidCsrf(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->assertInvalidCsrfToken($client, '/doctor/flush-log/rsetdzfukgli78t6r5uedtjfzkugl', $this->createUrl('/doctor'));
    }
}
