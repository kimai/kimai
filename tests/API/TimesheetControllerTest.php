<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\API\TimesheetController
 * @group integration
 */
class TimesheetControllerTest extends APIControllerBaseTest
{
    public function setUp()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setStartDate(new \DateTime('-10 days'))
        ;
        $this->importFixture($em, $fixture);
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/timesheets');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertInternalType('array', $result);
        $this->assertDefaultStructure($result);
    }

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'activity' => 1,
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertInternalType('array', $result);
        $this->assertDefaultStructure($result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(28800, $result['duration']);
        $this->assertEquals(2016, $result['rate']);
    }


    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/20');
    }

    protected function assertDefaultStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'begin', 'end', 'duration', 'rate'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'activity_id', 'user_id', 'description', 'fixed_rate', 'hourly_rate'
            ]);
        }

        $actual = array_keys($result);

        $this->assertEquals(count($expectedKeys), count($actual), 'Timesheet entity has different amount of keys');
        $this->assertEquals($expectedKeys, $actual, 'Timesheet structure does not match');
    }
}
