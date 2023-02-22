<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @group integration
 */
class FavoriteControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/favorite/timesheet/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(25);
        $fixture->setAmountRunning(2);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/favorite/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);
        self::assertStringContainsString('<a class="api-link text-decoration-none text-body d-block" href="/api/timesheets/', $content);
        self::assertStringContainsString('data-event="kimai.timesheetStart kimai.timesheetUpdate kimai.closeRemoteModal" data-method="PATCH" data-msg-error="timesheet', $content);
    }
}
