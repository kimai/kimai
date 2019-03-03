<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Component\HttpFoundation\Response;

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
            ->setAllowEmptyDescriptions(false)
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

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testGetCollectionForOtherUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole($em, User::ROLE_ADMIN))
            ->setStartDate(new \DateTime('-10 days'))
        ;
        $this->importFixture($em, $fixture);

        $query = ['user' => 2];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testGetCollectionForAllUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole($em, User::ROLE_ADMIN))
            ->setStartDate(new \DateTime('-10 days'))
        ;
        $this->importFixture($em, $fixture);

        $query = ['user' => 'all'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(17, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testGetCollectionForEmptyResult()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->assertAccessIsGranted($client, '/api/timesheets');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetCollectionWithQuery()
    {
        $query = ['customer' => 1, 'project' => 1, 'activity' => 1, 'page' => 2, 'size' => 5, 'order' => 'DESC', 'orderBy' => 'rate'];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertDefaultStructure($result);
    }

    public function testPostAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertDefaultStructure($result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(28800, $result['duration']);
        $this->assertEquals(2016, $result['rate']);
    }

    // check for project, as this is a required field. It will not be included in the select, as it is
    // already filtered within the repository due to the hidden customer
    public function testPostActionWithInvisibleProject()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customer = (new Customer())->setName('foo-bar-1')->setVisible(false)->setCountry('DE')->setTimezone('Euopre/Berlin');
        $em->persist($customer);
        $project = (new Project())->setName('foo-bar-2')->setVisible(true)->setCustomer($customer);
        $em->persist($project);
        $activity = (new Activity())->setName('foo-bar-3')->setVisible(true);
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertApiCallValidationError($client->getResponse(), ['project']);
    }

    // check for activity, as this is a required field. It will not be included in the select, as it is
    // already filtered within the repository due to the hidden flag
    public function testPostActionWithInvisibleActivity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $customer = (new Customer())->setName('foo-bar-1')->setVisible(true)->setCountry('DE')->setTimezone('Euopre/Berlin');
        $em->persist($customer);
        $project = (new Project())->setName('foo-bar-2')->setVisible(true)->setCustomer($customer);
        $em->persist($project);
        $activity = (new Activity())->setName('foo-bar-3')->setVisible(false);
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertApiCallValidationError($client->getResponse(), ['activity']);
    }

    public function testNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/20');
    }

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => (new \DateTime('- 7 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'exported' => true,
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertDefaultStructure($result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(25200, $result['duration']);
        $this->assertEquals(1, $result['exported']);
    }

    public function testPatchActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole($em, User::ROLE_TEAMLEAD))
            ->setStartDate(new \DateTime('-10 days'))
            ->setAllowEmptyDescriptions(false)
        ;
        $this->importFixture($em, $fixture);

        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => (new \DateTime('- 7 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'exported' => true,
        ];
        $this->request($client, '/api/timesheets/15', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('User cannot update timesheet', $json['message']);
    }

    public function testInvalidPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $data = [
            'activity' => 10,
            'project' => 1,
            'begin' => (new \DateTime())->format('Y-m-d H:m'),
            'end' => (new \DateTime('- 7 hours'))->format('Y-m-d H:m'),
            'description' => 'foo',
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['end', 'activity']);
    }

    protected function assertDefaultStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'begin', 'end', 'duration', 'description', 'rate', 'activity', 'project', 'tags', 'user'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'exported', 'fixed_rate', 'hourly_rate'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Timesheet structure does not match');
    }
}
