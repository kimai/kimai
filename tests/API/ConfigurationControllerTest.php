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
 * @covers \App\API\ConfigurationController
 * @covers \App\API\BaseApiController
 * @group integration
 */
class ConfigurationControllerTest extends APIControllerBaseTest
{
    public function testI18nIsSecure()
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
        $this->assertEquals(7, count($result));
        $this->assertStructure($result);
    }

    protected function assertStructure(array $result)
    {
        $expectedKeys = ['date', 'dateTime', 'duration', 'formDate', 'formDateTime', 'is24hours', 'time'];
        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Activity structure does not match');
    }
}
