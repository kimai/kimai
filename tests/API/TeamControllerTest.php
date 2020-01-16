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
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/api/teams');
        $this->assertUrlIsSecuredForRole(User::ROLE_TEAMLEAD, '/api/teams');
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

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'teamlead' => 1,
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $data = [
            'name' => 'foo',
            'teamlead' => 1,
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Access denied.', $json['message']);
    }

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'teamlead' => 1,
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'teamlead' => 2,
            'users' => [1, 5, 4]
        ];
        $this->request($client, '/api/teams/' . $result['id'], 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertStructure($result);
        $this->assertNotEmpty($result['id']);
        $this->assertCount(4, $result['users']);
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
