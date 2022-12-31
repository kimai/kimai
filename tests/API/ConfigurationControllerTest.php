<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;

/**
 * @group integration
 */
class ConfigurationControllerTest extends APIControllerBaseTest
{
    public function testIsTimesheetSecure()
    {
        $this->assertUrlIsSecured('/api/config/timesheet');
    }

    public function testGetTimesheet()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/config/timesheet', 'GET');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $expectedKeys = ['activeEntriesHardLimit', 'defaultBeginTime', 'isAllowFutureTimes', 'isAllowOverlapping', 'trackingMode'];
        $this->assertCount(\count($expectedKeys), $result);
        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Config structure does not match');
    }
}
