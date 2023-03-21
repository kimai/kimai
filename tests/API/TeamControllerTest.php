<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Team;
use App\Entity\User;
use App\Tests\DataFixtures\TeamFixtures;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class TeamControllerTest extends APIControllerBaseTest
{
    /**
     * @return Team[]
     */
    protected function importTeamFixtures(): array
    {
        $fixture = new TeamFixtures();
        $fixture->setAmount(1);

        return $this->importFixture($fixture);
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/teams');
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getRoleTestData(): array
    {
        return [
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
        ];
    }

    /**
     * @dataProvider getRoleTestData
     */
    public function testIsSecureForRole(string $role): void
    {
        $this->assertUrlIsSecuredForRole($role, '/api/teams');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importTeamFixtures();
        $this->assertAccessIsGranted($client, '/api/teams');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        self::assertEquals(2, \count($result));
        self::assertApiResponseTypeStructure('TeamCollection', $result[0]);
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $teams = $this->importTeamFixtures();
        $id = $teams[0]->getId();

        $this->assertAccessIsGranted($client, '/api/teams/' . $id);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/teams/' . PHP_INT_MAX, 'GET', 'App\\Entity\\Team object not found by the @ParamConverter annotation.');
    }

    public function testDeleteActionWithUnknownTeam(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/teams/' . PHP_INT_MAX);
    }

    public function testPostAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1]
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testPostActionWithInvalidUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $data = [
            'name' => 'foo',
            'teamlead' => 1,
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response, 'Access denied.');
    }

    public function testPostActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => '',
            'members' => [
                ['user' => 9999, 'teamlead' => 1]
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name', 'members.0.user']);
    }

    public function testPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => true],
                ['user' => 5, 'teamlead' => true],
            ]
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        $updateId = $result['id'];

        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 2, 'teamlead' => true],
                ['user' => 1, 'teamlead' => false],
                ['user' => 4, 'teamlead' => true],
            ]
        ];

        $this->request($client, '/api/teams/' . $updateId, 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        $this->assertNotEmpty($result['id']);
        self::assertCount(3, $result['members']);

        $this->request($client, '/api/teams/' . $updateId);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(3, $result['members']);

        self::assertFalse($result['members'][1]['teamlead']);
        self::assertEquals(1, $result['members'][1]['user']['id']);
        self::assertEquals('clara_customer', $result['members'][1]['user']['username']);

        self::assertTrue($result['members'][2]['teamlead']);
        self::assertEquals(4, $result['members'][2]['user']['id']);
        self::assertEquals('tony_teamlead', $result['members'][2]['user']['username']);

        self::assertTrue(true, $result['members'][0]['teamlead']);
        self::assertEquals(2, $result['members'][0]['user']['id']);
        self::assertEquals('john_user', $result['members'][0]['user']['username']);
    }

    public function testPatchActionWithValidationErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        $data = [
            'name' => '1',
            'members' => [
                ['user' => 9999, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams/' . $result['id'], 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name', 'members.0.user']);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $teams = $this->importTeamFixtures();
        $id = $teams[0]->getId();
        $this->assertAccessIsGranted($client, '/api/teams/' . $id);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        $this->assertNotEmpty($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/teams/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testPostMemberAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1]
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['members']);

        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(2, $result['members']);
    }

    public function testPostMemberActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/members/999');

        //  user not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/members/999');

        // add user
        $this->request($client, '/api/teams/' . $result['id'] . '/members/5', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // cannot add existing member
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/members/5', 'POST');
    }

    public function testDeleteMemberAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(4, $result['members']);

        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(3, $result['members']);
    }

    public function testDeleteMemberActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/members/999');

        //  user not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/members/999');

        // remove user
        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // cannot remove non-member
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/members/2', 'DELETE');

        // cannot remove teamlead
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/members/1', 'DELETE');
    }

    public function testPostCustomerAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['customers']);

        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(1, $result['customers']);
        self::assertEquals(1, $result['customers'][0]['id']);
    }

    public function testPostCustomerActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/customers/999');

        //  customer not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/customers/999');

        // add customer
        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['customers']);

        // cannot add existing customer
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
    }

    public function testDeleteCustomerAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['customers']);

        // add customer
        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['customers']);

        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(0, $result['customers']);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $team = $em->getRepository(Team::class)->find($result['id']);
        self::assertInstanceOf(Team::class, $team);
        self::assertCount(0, $team->getCustomers());
    }

    public function testDeleteCustomerActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/customers/999');

        //  customer not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/customers/999');

        // cannot remove customer
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/customers/1', 'DELETE');
    }

    public function testPostProjectAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['projects']);
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(1, $result['projects']);
        self::assertEquals(1, $result['projects'][0]['id']);
    }

    public function testPostProjectActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/projects/999');

        $this->request($client, '/api/teams/999/projects/999', 'POST');

        //  project not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/projects/999');

        // add project
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['projects']);

        // cannot add existing project
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
    }

    public function testDeleteProjectAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['projects']);

        // add project
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['projects']);

        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(0, $result['projects']);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $team = $em->getRepository(Team::class)->find($result['id']);
        self::assertInstanceOf(Team::class, $team);
        self::assertCount(0, $team->getProjects());
    }

    public function testDeleteProjectActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/projects/999');

        //  project not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/projects/999');

        // cannot remove project
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/projects/1', 'DELETE');
    }

    public function testPostActivityAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['activities']);
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(1, $result['activities']);
        self::assertEquals(1, $result['activities'][0]['id']);
    }

    public function testPostActivityActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/activities/999');

        //  activity not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/activities/999');

        // add activity
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['activities']);

        // cannot add existing activity
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
    }

    public function testDeleteActivityAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(0, $result['activities']);

        // add activity
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $result['activities']);

        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertCount(0, $result['activities']);
    }

    public function testDeleteActivityActionErrors(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 1, 'teamlead' => 1],
                ['user' => 2, 'teamlead' => 0],
                ['user' => 4, 'teamlead' => 0],
                ['user' => 5, 'teamlead' => 0],
            ],
        ];
        $this->request($client, '/api/teams', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/activities/9999');

        //  activity not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/activities/9999');

        // cannot remove activity
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/activities/1', 'DELETE');
    }
}
