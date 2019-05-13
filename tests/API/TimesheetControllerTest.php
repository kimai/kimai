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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \App\API\TimesheetController
 * @group integration
 */
class TimesheetControllerTest extends APIControllerBaseTest
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public function setUp()
    {
        $this->importFixtureForUser(User::ROLE_USER);
    }

    protected function importFixtureForUser(string $role)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole($em, $role))
            ->setStartDate((new \DateTime('first day of this month'))->setTime(0, 0, 1))
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
        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'customer' => 1,
            'project' => 1,
            'activity' => 1,
            'page' => 2,
            'size' => 5,
            'order' => 'DESC',
            'orderBy' => 'rate',
            'active' => 0,
            'begin' => $begin->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'exported' => 0,
        ];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testExportedFilter()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setExported(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setStartDate(new \DateTime('first day of this month'))
            ->setAllowEmptyDescriptions(false)
        ;
        $this->importFixture($em, $fixture);

        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'exported' => 1,
        ];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, count($result));
        $this->assertDefaultStructure($result[0], false);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'exported' => 0,
        ];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, count($result));
        $this->assertDefaultStructure($result[0], false);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(17, count($result));
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

    public function testGetEntityAccessDenied()
    {
        $this->importFixtureForUser(User::ROLE_ADMIN);

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertApiAccessDenied($client, '/api/timesheets/15', 'You are not allowed to view this timesheet');
    }

    public function testGetEntityAccessAllowedForAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertDefaultStructure($result);
    }

    public function testGetEntityNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/20');
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
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m:s'),
            'end' => (new \DateTime())->format('Y-m-d H:m:s'),
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

    public function testPatchAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => (new \DateTime('- 7 hours'))->format('Y-m-d\TH:m'),
            'end' => (new \DateTime())->format('Y-m-d\TH:m'),
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
            'begin' => (new \DateTime('- 7 hours'))->format('Y-m-d\TH:m:s'),
            'end' => (new \DateTime())->format('Y-m-d\TH:m:s'),
            'description' => 'foo',
            'exported' => true,
        ];
        $this->request($client, '/api/timesheets/15', 'PATCH', [], json_encode($data));
        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('You are not allowed to update this timesheet', $json['message']);
    }

    public function testPatchActionWithUnknownTimesheet()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/255', []);
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

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertDefaultStructure($result);
        $this->assertNotEmpty($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());

        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/' . $id);
    }

    public function testDeleteActionWithUnknownTimesheet()
    {
        $this->assertEntityNotFoundForDelete(User::ROLE_ADMIN, '/api/timesheets/255', []);
    }

    public function testDeleteActionForDifferentUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = 1;

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());

        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/' . $id);
    }

    public function testDeleteActionWithoutAuthorization()
    {
        $this->importFixtureForUser(User::ROLE_ADMIN);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/timesheets/15', 'DELETE');

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('You are not allowed to delete this timesheet', $json['message']);
    }

    public function testGetRecentCollectionWithSubresources()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole($em, User::ROLE_ADMIN))
            ->setStartDate($start)
        ;
        $this->importFixture($em, $fixture);

        $query = [
            'user' => 'all',
            'size' => 2,
            'begin' => $start->format(self::DATE_FORMAT),
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets/recent', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, count($result));
        $this->assertDefaultStructure($result[0], false);
        $this->assertHasSubresources($result[0]);
    }

    public function testActiveAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/api/timesheets/active');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $results = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(3, count($results));
        foreach ($results as $timesheet) {
            $this->assertDefaultStructure($timesheet, false);
        }
    }

    public function testStopAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole($em, User::ROLE_USER))
            ->setStartDate($start)
            ->setAmountRunning(1)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/api/timesheets/11/stop', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
    }

    public function testStopActionFailsOnStoppedEntry()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/api/timesheets/1/stop', 'PATCH');

        $this->assertApiException($client->getResponse(), 'Timesheet entry already stopped');
    }

    public function testStopThrowsNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/11/stop', 'PATCH');
    }

    public function testStopNotAllowedForUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(2)
            ->setUser($this->getUserByRole($em, User::ROLE_ADMIN))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/api/timesheets/12/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to stop this timesheet');
    }

    public function testGetCollectionWithTags()
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
            ->setUseTags(true)
            ->setTags(['Test', 'Administration']);
        $this->importFixture($em, $fixture);

        $query = ['tags' => 'Test'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, count($result));
        $this->assertDefaultStructure($result[0], false);

        $query = ['tags' => 'Test,Admin'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, count($result));
        $this->assertDefaultStructure($result[0], false);

        $query = ['tags' => 'Nothing'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(20, count($result));
        $this->assertDefaultStructure($result[0], false);
    }

    public function testRestartAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $data = [
            'description' => 'foo',
            'tags' => 'another,testing,bar'
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));

        $this->request($client, '/api/timesheets/1/restart', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertDefaultStructure($result, true);
        $this->assertEmpty($result['description']);
        $this->assertEmpty($result['tags']);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals(1, $timesheet->getActivity()->getId());
        $this->assertEquals(1, $timesheet->getProject()->getId());
        $this->assertEmpty($timesheet->getDescription());
        $this->assertEmpty($timesheet->getTags());
    }

    public function testRestartActionWithCopyData()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $data = [
            'description' => 'foo',
            'tags' => 'another,testing,bar'
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertEquals('foo', $timesheet->getDescription());

        $this->request($client, '/api/timesheets/1/restart', 'PATCH', ['copy' => 'all']);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertDefaultStructure($result, true);
        $this->assertEquals('foo', $result['description']);
        $this->assertEquals(['another', 'testing', 'bar'], $result['tags']);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals(1, $timesheet->getActivity()->getId());
        $this->assertEquals(1, $timesheet->getProject()->getId());
        $this->assertEquals('foo', $timesheet->getDescription());
        $this->assertEquals(['another', 'testing', 'bar'], $timesheet->getTagsAsArray());
    }

    public function testRestartNotAllowedForUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(2)
            ->setUser($this->getUserByRole($em, User::ROLE_ADMIN))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($em, $fixture);

        $this->request($client, '/api/timesheets/12/restart', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to re-start this timesheet');
    }

    protected function assertDefaultStructure(array $result, $full = true)
    {
        $expectedKeys = [
            'id', 'begin', 'end', 'duration', 'description', 'rate', 'activity', 'project', 'tags', 'user'
        ];

        if ($full) {
            $expectedKeys = array_merge($expectedKeys, [
                'exported', 'fixedRate', 'hourlyRate'
            ]);
        }

        $actual = array_keys($result);
        sort($actual);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $actual, 'Timesheet structure does not match');
    }

    protected function assertHasSubresources(array $result)
    {
        $this->assertArrayHasKey('activity', $result);
        $this->assertArrayHasKey('id', $result['activity']);
        $this->assertArrayHasKey('name', $result['activity']);
        $this->assertArrayHasKey('visible', $result['activity']);
        $this->assertArrayHasKey('project', $result['activity']);

        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('id', $result['project']);
        $this->assertArrayHasKey('name', $result['project']);
        $this->assertArrayHasKey('visible', $result['project']);
        $this->assertArrayHasKey('customer', $result['project']);
        $this->assertArrayHasKey('id', $result['project']['customer']);
        $this->assertArrayHasKey('name', $result['project']['customer']);
        $this->assertArrayHasKey('visible', $result['project']['customer']);
    }
}
