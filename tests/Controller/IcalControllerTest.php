<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Calendar\IcsValidator;
use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\SecurityServiceFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Controller\IcalController
 * @group integration
 */
class IcalControllerTest extends ControllerBaseTest
{
    protected function setUp(): void
    {
        $this->client = $this->createClient();
        $this->importFixture(TimesheetFixtures::class);
    }

    public function testGetGlobalEvents(): void
    {
        $this->request('/ical/events/global', 'GET');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertIsArray($this->jsonResponse());
    }

    public function testGetUserEvents(): void
    {
        $this->request('/ical/events/user', 'GET');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertIsArray($this->jsonResponse());
    }

    public function testGetInvalidEventType(): void
    {
        $this->request('/ical/events/invalid', 'GET');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertIsArray($this->jsonResponse());
        $this->assertEmpty($this->jsonResponse());
    }

    public function testGetEventsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/ical/events/global');
        $this->assertIsRedirect($this->client->getResponse());
    }
} 