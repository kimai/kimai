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
class ConfigurationControllerTest extends APIControllerBaseTestCase
{
    public function testIsTimesheetSecure(): void
    {
        $this->assertUrlIsSecured('/api/config/timesheet');
    }

    public function testGetTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/config/timesheet', 'GET');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        $expectedKeys = ['activeEntriesHardLimit', 'defaultBeginTime', 'isAllowFutureTimes', 'isAllowOverlapping', 'trackingMode'];
        self::assertCount(\count($expectedKeys), $result);
        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        self::assertEquals($expectedKeys, $actual, 'Config structure does not match');
    }

    public function testIsColorsSecure(): void
    {
        $this->assertUrlIsSecured('/api/config/colors');
    }

    public function testGetColors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/config/colors', 'GET');
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $actual = json_decode($content, true);

        self::assertIsArray($actual);
        self::assertNotEmpty($actual);
        $expected = [
            'Silver' => '#c0c0c0',
            'Gray' => '#808080',
            'Black' => '#000000',
            'Maroon' => '#800000',
            'Brown' => '#a52a2a',
            'Red' => '#ff0000',
            'Orange' => '#ffa500',
            'Gold' => '#ffd700',
            'Yellow' => '#ffff00',
            'Peach' => '#ffdab9',
            'Khaki' => '#f0e68c',
            'Olive' => '#808000',
            'Lime' => '#00ff00',
            'Jelly' => '#9acd32',
            'Green' => '#008000',
            'Teal' => '#008080',
            'Aqua' => '#00ffff',
            'LightBlue' => '#add8e6',
            'DeepSky' => '#00bfff',
            'Dodger' => '#1e90ff',
            'Blue' => '#0000ff',
            'Navy' => '#000080',
            'Purple' => '#800080',
            'Fuchsia' => '#ff00ff',
            'Violet' => '#ee82ee',
            'Rose' => '#ffe4e1',
            'Lavender' => '#E6E6FA',
        ];
        self::assertCount(\count($expected), $actual);

        self::assertEquals($expected, $actual, 'Color structure does not match');
    }
}
