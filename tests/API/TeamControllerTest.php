<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\DataFixtures\UserFixtures;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Team;
use App\Entity\User;
use App\Tests\DataFixtures\TeamFixtures;
use App\User\PermissionService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class TeamControllerTest extends APIControllerBaseTestCase
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
    public static function getRoleTestData(): array
    {
        return [
            [User::ROLE_USER],
            [User::ROLE_TEAMLEAD],
        ];
    }

    #[DataProvider('getRoleTestData')]
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

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(2, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TeamCollection', $result[0]);
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $teams = $this->importTeamFixtures();
        $id = $teams[0]->getId();

        $this->assertAccessIsGranted($client, '/api/teams/' . $id);
        $result = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/teams/' . PHP_INT_MAX);
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
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertNotEmpty($result['id']);
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
        self::assertEquals(400, $response->getStatusCode());
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        $updateId = $result['id'];
        self::assertIsNumeric($updateId);

        $data = [
            'name' => 'foo',
            'members' => [
                ['user' => 2, 'teamlead' => true],
                ['user' => 1, 'teamlead' => false],
                ['user' => 4, 'teamlead' => true],
            ]
        ];

        $this->request($client, '/api/teams/' . $updateId, 'PATCH', [], json_encode($data));
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsArray($result['members']);
        self::assertCount(3, $result['members']);
        self::assertIsNumeric($updateId);

        $this->request($client, '/api/teams/' . $updateId);
        $result = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['members']);
        self::assertCount(3, $result['members']);

        self::assertIsArray($result['members'][0]);
        self::assertTrue($result['members'][0]['teamlead']);
        self::assertIsArray($result['members'][0]['user']);
        self::assertEquals(2, $result['members'][0]['user']['id']);
        self::assertEquals('john_user', $result['members'][0]['user']['username']);

        self::assertIsArray($result['members'][1]);
        self::assertFalse($result['members'][1]['teamlead']);
        self::assertIsArray($result['members'][1]['user']);
        self::assertEquals(1, $result['members'][1]['user']['id']);
        self::assertEquals('clara_customer', $result['members'][1]['user']['username']);

        self::assertIsArray($result['members'][2]);
        self::assertTrue($result['members'][2]['teamlead']);
        self::assertIsArray($result['members'][2]['user']);
        self::assertEquals(4, $result['members'][2]['user']['id']);
        self::assertEquals('tony_teamlead', $result['members'][2]['user']['username']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        $data = [
            'name' => '1',
            'members' => [
                ['user' => 9999, 'teamlead' => 1],
            ],
        ];
        $this->request($client, '/api/teams/' . $result['id'], 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['name', 'members.0.user']);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $teams = $this->importTeamFixtures();
        $id = $teams[0]->getId();
        $this->assertAccessIsGranted($client, '/api/teams/' . $id);
        $result = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertNotEmpty($result['id']);
        $id = $result['id'];
        self::assertIsNumeric($id);

        $this->request($client, '/api/teams/' . $id, 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsArray($result['members']);
        self::assertCount(1, $result['members']);
        self::assertIsNumeric($result['id']);

        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['members']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/members/999');

        //  user not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/members/999');

        // add user
        $this->request($client, '/api/teams/' . $result['id'] . '/members/5', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());

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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['members']);
        self::assertCount(4, $result['members']);

        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['members']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/members/999');

        //  user not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/members/999');

        // remove user
        $this->request($client, '/api/teams/' . $result['id'] . '/members/2', 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());

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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['customers']);
        self::assertCount(0, $result['customers']);

        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['customers']);
        self::assertCount(1, $result['customers']);
        self::assertIsArray($result['customers'][0]);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/customers/999');

        //  customer not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/customers/999');

        // add customer
        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['customers']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['customers']);
        self::assertCount(0, $result['customers']);

        // add customer
        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['customers']);
        self::assertCount(1, $result['customers']);

        $this->request($client, '/api/teams/' . $result['id'] . '/customers/1', 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['customers']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['projects']);
        self::assertCount(0, $result['projects']);
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['projects']);
        self::assertCount(1, $result['projects']);
        self::assertIsArray($result['projects'][0]);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/projects/999');

        $this->request($client, '/api/teams/999/projects/999', 'POST');

        //  project not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/projects/999');

        // add project
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['projects']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['projects']);
        self::assertCount(0, $result['projects']);

        // add project
        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsArray($result['projects']);
        self::assertCount(1, $result['projects']);
        self::assertIsInt($result['id']);

        $this->request($client, '/api/teams/' . $result['id'] . '/projects/1', 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['projects']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['activities']);
        self::assertCount(0, $result['activities']);
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['activities']);
        self::assertCount(1, $result['activities']);
        self::assertIsArray($result['activities'][0]);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/999/activities/999');

        //  activity not found
        $this->assertEntityNotFoundForPost($client, '/api/teams/' . $result['id'] . '/activities/999');

        // add activity
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['activities']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsArray($result['activities']);
        self::assertCount(0, $result['activities']);
        self::assertIsInt($result['id']);

        // add activity
        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'POST');
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);
        self::assertIsArray($result['activities']);
        self::assertCount(1, $result['activities']);

        $this->request($client, '/api/teams/' . $result['id'] . '/activities/1', 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TeamEntity', $result);
        self::assertIsArray($result['activities']);
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
        self::assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($result);
        self::assertIsNumeric($result['id']);

        //  team not found
        $this->assertNotFoundForDelete($client, '/api/teams/999/activities/9999');

        //  activity not found
        $this->assertNotFoundForDelete($client, '/api/teams/' . $result['id'] . '/activities/9999');

        // cannot remove activity
        $this->assertBadRequest($client, '/api/teams/' . $result['id'] . '/activities/1', 'DELETE');
    }

    /**
     * Sets up tony_teamlead so that he has the `edit_team` permission via a
     * dedicated test role, and makes him the teamlead of a fresh team.
     *
     * This simulates an installation that lets teamleads manage their own
     * teams. The permission is routed through PermissionService so the shared
     * cache is invalidated and the request kernel sees the new permission.
     *
     * @return Team the team the attacker is teamlead of
     */
    private function prepareAttackerTeamleadWithEditTeam(string $suffix): Team
    {
        $em = $this->getEntityManager();

        $roleName = 'TEST_EDIT_TEAM_' . $suffix;
        $role = (new Role())->setName($roleName);
        $permission = (new RolePermission())->setRole($role)->setPermission('edit_team')->setAllowed(true);
        $em->persist($role);
        self::getContainer()->get(PermissionService::class)->saveRolePermission($permission);

        $attacker = $this->getUserByName(UserFixtures::USERNAME_TEAMLEAD);
        $attacker->addRole($roleName);
        $em->persist($attacker);

        $attackerTeam = new Team('GHSA-xv4r attacker team ' . $suffix);
        $attackerTeam->addTeamlead($attacker);
        $em->persist($attackerTeam);

        $em->flush();

        return $attackerTeam;
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg.
     *
     * A teamlead with edit_team permission must not be able to add a user
     * that falls outside their authorized management scope by calling the
     * member-assignment API directly. The frontend hides those users; the
     * backend has to enforce the same boundary.
     */
    public function testPostMemberActionDeniesUserOutsideTeamleadScope(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $attackerTeam = $this->prepareAttackerTeamleadWithEditTeam('GHSA_XV4R_MEMBER');

        // target user is in a separate team that the attacker has no role in,
        // and the target is not a "regular-user-only without any teams" (which
        // would otherwise be visible to any teamlead).
        $target = $this->getUserByName(UserFixtures::USERNAME_USER);
        $isolatedTeam = new Team('GHSA-xv4r isolated team');
        $isolatedTeam->addUser($target);
        $isolatedTeam->addTeamlead($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        $em->persist($isolatedTeam);
        $em->flush();

        $teamId = $attackerTeam->getId();
        $targetId = $target->getId();
        self::assertIsInt($teamId);
        self::assertIsInt($targetId);

        $this->request($client, '/api/teams/' . $teamId . '/members/' . $targetId, 'POST');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // verify the relation was NOT persisted
        $em->clear();
        $reloaded = $em->getRepository(Team::class)->find($teamId);
        self::assertInstanceOf(Team::class, $reloaded);
        self::assertFalse($reloaded->hasUser($target));
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg.
     *
     * The teamlead must not be able to attach an activity that they cannot
     * view in the first place, even when they may edit the team.
     */
    public function testPostActivityActionDeniesActivityOutsideTeamleadScope(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $attackerTeam = $this->prepareAttackerTeamleadWithEditTeam('GHSA_XV4R_ACTIVITY');

        // activity is created without any team relation that the attacker is part of
        $customer = new Customer('GHSA-xv4r activity customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $project = new Project();
        $project->setName('GHSA-xv4r activity project');
        $project->setCustomer($customer);
        $em->persist($project);

        $activity = new Activity();
        $activity->setName('GHSA-xv4r out-of-scope activity');
        $activity->setProject($project);
        $em->persist($activity);

        $em->flush();

        $teamId = $attackerTeam->getId();
        $activityId = $activity->getId();
        self::assertIsInt($teamId);
        self::assertIsInt($activityId);

        $this->request($client, '/api/teams/' . $teamId . '/activities/' . $activityId, 'POST');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $em->clear();
        $reloaded = $em->getRepository(Team::class)->find($teamId);
        self::assertInstanceOf(Team::class, $reloaded);
        $reloadedActivity = $em->getRepository(Activity::class)->find($activityId);
        self::assertInstanceOf(Activity::class, $reloadedActivity);
        self::assertFalse($reloaded->hasActivity($reloadedActivity));
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg (postCustomerAction variant).
     *
     * A teamlead with edit_team permission must not be able to grant their
     * team access to a customer that they cannot view themselves. The bug
     * pattern is identical to the postActivityAction variant.
     */
    public function testPostCustomerActionDeniesCustomerOutsideTeamleadScope(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $attackerTeam = $this->prepareAttackerTeamleadWithEditTeam('GHSA_XV4R_CUSTOMER');

        // customer has no team relation to the attacker -> attacker has no view permission on it
        $customer = new Customer('GHSA-xv4r out-of-scope customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);
        $em->flush();

        $teamId = $attackerTeam->getId();
        $customerId = $customer->getId();
        self::assertIsInt($teamId);
        self::assertIsInt($customerId);

        $this->request($client, '/api/teams/' . $teamId . '/customers/' . $customerId, 'POST');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $em->clear();
        $reloaded = $em->getRepository(Team::class)->find($teamId);
        self::assertInstanceOf(Team::class, $reloaded);
        $reloadedCustomer = $em->getRepository(Customer::class)->find($customerId);
        self::assertInstanceOf(Customer::class, $reloadedCustomer);
        self::assertFalse($reloaded->hasCustomer($reloadedCustomer));
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg (postProjectAction variant).
     *
     * A teamlead with edit_team permission must not be able to grant their
     * team access to a project that they cannot view themselves.
     */
    public function testPostProjectActionDeniesProjectOutsideTeamleadScope(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $attackerTeam = $this->prepareAttackerTeamleadWithEditTeam('GHSA_XV4R_PROJECT');

        $customer = new Customer('GHSA-xv4r project customer');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);

        $project = new Project();
        $project->setName('GHSA-xv4r out-of-scope project');
        $project->setCustomer($customer);
        $em->persist($project);
        $em->flush();

        $teamId = $attackerTeam->getId();
        $projectId = $project->getId();
        self::assertIsInt($teamId);
        self::assertIsInt($projectId);

        $this->request($client, '/api/teams/' . $teamId . '/projects/' . $projectId, 'POST');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $em->clear();
        $reloaded = $em->getRepository(Team::class)->find($teamId);
        self::assertInstanceOf(Team::class, $reloaded);
        $reloadedProject = $em->getRepository(Project::class)->find($projectId);
        self::assertInstanceOf(Project::class, $reloadedProject);
        self::assertFalse($reloaded->hasProject($reloadedProject));
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg (patchAction variant).
     *
     * The PATCH /api/teams/{id} endpoint takes a `members` array and replaces
     * the team's membership. A teamlead with edit_team permission must not be
     * able to attach an out-of-scope user this way.
     */
    public function testPatchActionDeniesAddingOutOfScopeMember(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        $attackerTeam = $this->prepareAttackerTeamleadWithEditTeam('GHSA_XV4R_PATCH');

        $attacker = $this->getUserByName(UserFixtures::USERNAME_TEAMLEAD);
        $attackerId = $attacker->getId();
        self::assertIsInt($attackerId);

        // target user kept out of attacker's reach
        $target = $this->getUserByName(UserFixtures::USERNAME_USER);
        $isolatedTeam = new Team('GHSA-xv4r isolated team patch');
        $isolatedTeam->addUser($target);
        $isolatedTeam->addTeamlead($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        $em->persist($isolatedTeam);
        $em->flush();

        $teamId = $attackerTeam->getId();
        $targetId = $target->getId();
        self::assertIsInt($teamId);
        self::assertIsInt($targetId);

        $payload = [
            'name' => 'GHSA-xv4r patch team',
            'members' => [
                ['user' => $attackerId, 'teamlead' => true],
                ['user' => $targetId, 'teamlead' => false],
            ],
        ];

        $this->request($client, '/api/teams/' . $teamId, 'PATCH', [], json_encode($payload));

        $response = $client->getResponse();
        // either a hard 403 or a validation rejection of the members field is acceptable;
        // any 2xx that ends with the target attached to the team is the security failure.
        self::assertFalse(
            $response->isSuccessful() && \str_contains((string) $response->getContent(), '"id"'),
            'PATCH /api/teams must not silently attach an out-of-scope user via the members array.'
        );

        $em->clear();
        $reloaded = $em->getRepository(Team::class)->find($teamId);
        self::assertInstanceOf(Team::class, $reloaded);
        self::assertFalse(
            $reloaded->hasUser($target),
            'Out-of-scope user must not have been added to the team via PATCH.'
        );
    }

    /**
     * Regression test for GHSA-xv4r-4885-gwpg (postAction variant).
     *
     * The POST /api/teams endpoint accepts a `members` array. A user whose
     * role grants `create_team` but not `view_all_data` must not be able to
     * create a team with members they cannot manage. This covers the
     * non-admin "team creator" role configuration.
     */
    public function testPostActionDeniesCreatingTeamWithOutOfScopeMember(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();

        // grant create_team to a custom role and attach it to tony_teamlead
        $roleName = 'TEST_CREATE_TEAM_GHSA_XV4R';
        $role = (new Role())->setName($roleName);
        $permission = (new RolePermission())->setRole($role)->setPermission('create_team')->setAllowed(true);
        $em->persist($role);
        self::getContainer()->get(PermissionService::class)->saveRolePermission($permission);

        $attacker = $this->getUserByName(UserFixtures::USERNAME_TEAMLEAD);
        $attacker->addRole($roleName);
        $em->persist($attacker);

        $attackerId = $attacker->getId();
        self::assertIsInt($attackerId);

        // target user is unreachable for the attacker
        $target = $this->getUserByName(UserFixtures::USERNAME_USER);
        $isolatedTeam = new Team('GHSA-xv4r isolated team create');
        $isolatedTeam->addUser($target);
        $isolatedTeam->addTeamlead($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        $em->persist($isolatedTeam);
        $em->flush();

        $targetId = $target->getId();
        self::assertIsInt($targetId);

        $payload = [
            'name' => 'GHSA-xv4r created team',
            'members' => [
                ['user' => $attackerId, 'teamlead' => true],
                ['user' => $targetId, 'teamlead' => false],
            ],
        ];

        $this->request($client, '/api/teams', 'POST', [], json_encode($payload));

        $response = $client->getResponse();
        $body = (string) $response->getContent();

        // success body would contain the new id and the target as a member -> security failure
        if ($response->isSuccessful()) {
            $decoded = json_decode($body, true);
            self::assertIsArray($decoded);
            $memberIds = [];
            if (\is_array($decoded['members'] ?? null)) {
                foreach ($decoded['members'] as $entry) {
                    if (\is_array($entry) && \is_array($entry['user'] ?? null) && isset($entry['user']['id'])) {
                        $memberIds[] = $entry['user']['id'];
                    }
                }
            }
            self::assertNotContains(
                $targetId,
                $memberIds,
                'POST /api/teams must not silently accept an out-of-scope user in the members array.'
            );
        }
    }
}
