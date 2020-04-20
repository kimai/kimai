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
    public function testIsI18nSecure()
    {
        $this->assertUrlIsSecured('/api/config/i18n');
    }

    public function testGetI18n()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/config/i18n', 'GET');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
        $this->assertI18nStructure($result);
    }

    protected function assertI18nStructure(array $result)
    {
        $expectedKeys = ['date', 'dateTime', 'duration', 'formDate', 'formDateTime', 'is24hours', 'time'];
        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Config structure does not match');
    }

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
        $this->assertEquals(5, \count($result));
        $this->assertTimesheetStructure($result);
    }

    protected function assertTimesheetStructure(array $result)
    {
        $expectedKeys = ['activeEntriesHardLimit', 'activeEntriesSoftLimit', 'defaultBeginTime', 'isAllowFutureTimes', 'trackingMode'];
        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Config structure does not match');
    }
}
