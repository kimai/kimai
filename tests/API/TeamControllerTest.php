<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\User;
use App\Tests\DataFixtures\TeamFixtures;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class TeamControllerTest extends APIControllerBaseTest
{
    protected function setUp(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TeamFixtures();
        $fixture->setAmount(1);
        $this->importFixture($em, $fixture);
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/teams');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/teams');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(2, count($result));
        $this->assertStructure($result[0], false);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/teams/2');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result, true);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_ADMIN, '/api/teams/3');
    }

    public function testDeleteActionWithUnknownTeam()
    {
        $this->assertEntityNotFoundForDelete(User::ROLE_ADMIN, '/api/teams/255', []);
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/teams/2');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/teams/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());

        $this->assertEntityNotFound(User::ROLE_ADMIN, '/api/teams/' . $id);
    }

    protected function assertStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'name'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'teamlead', 'users'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Team structure does not match');
    }
}
