<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\API\BaseApiController;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\Security\UserDateTimeFactoryFactory;
use App\Tests\Mocks\TimesheetTestMetaFieldSubscriberMock;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class TimesheetControllerTest extends APIControllerBaseTest
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';
    public const DATE_FORMAT_HTML5 = 'Y-m-d\TH:i:s';
    public const TEST_TIMEZONE = 'Europe/London';

    protected function importFixtureForUser(string $role)
    {
        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole($role))
            ->setAllowEmptyDescriptions(false)
            ->setStartDate((new \DateTime('first day of this month'))->setTime(0, 0, 1))
        ;

        $this->importFixture($fixture);
    }

    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/api/timesheets');
    }

    public function testGetCollection()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionFull()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', ['full' => 'true']);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollectionFull', $result[0]);
    }

    public function testGetCollectionForOtherUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importFixtureForUser(User::ROLE_USER);
        $em = $this->getEntityManager();

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setStartDate(new \DateTime('-10 days'))
        ;
        $this->importFixture($fixture);

        $query = ['user' => 2];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionForAllUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setStartDate(new \DateTime('-10 days'))
        ;
        $this->importFixture($fixture);

        $query = ['user' => 'all'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(17, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
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
        $modifiedAfter = new \DateTime('-1 hour');
        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'customers' => '1',
            'projects' => '1',
            'activities' => '1',
            'page' => 2,
            'size' => 5,
            'order' => 'DESC',
            'orderBy' => 'rate',
            'active' => 0,
            'modified_after' => $modifiedAfter->format(self::DATE_FORMAT_HTML5),
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionWithSingleParamsQuery()
    {
        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'customer' => '1',
            'project' => '1',
            'activity' => '1',
            'page' => 2,
            'size' => 5,
            'order' => 'DESC',
            'orderBy' => 'rate',
            'active' => 0,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testExportedFilter()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setExported(true)
            ->setAmount(7)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setStartDate(new \DateTime('first day of this month'))
            ->setAllowEmptyDescriptions(false)
        ;
        $this->importFixture($fixture);

        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 1,
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(7, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
        ];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(17, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetEntity()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $this->getEntityManager();

        $startDate = new \DateTime('2020-03-27 14:35:59', new \DateTimeZone('Pacific/Tongatapu'));
        $endDate = (clone $startDate)->modify('+ 46385 seconds');
        $project = $em->getRepository(Project::class)->find(1);
        $activity = $em->getRepository(Activity::class)->find(1);

        $tag = new Tag();
        $tag->setName('test');
        $em->persist($tag);

        $timesheet = new Timesheet();
        $timesheet
            ->setHourlyRate(137.21)
            ->setInternalRate(64.96)
            ->setBegin($startDate)
            ->setEnd($endDate)
            ->setExported(true)
            ->setDescription('**foo**' . PHP_EOL . 'bar')
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setProject($project)
            ->setActivity($activity)
            ->addTag($tag)
        ;
        $em->persist($timesheet);

        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $expected = [
            'activity' => 1,
            'project' => 1,
            'user' => 2,
            'tags' => [
                0 => 'test'
            ],
            'id' => 1,
            // make sure the timezone is properly applied in serializer (see #1858)
            // minute and second are different from the above datetime object, because of applied default minute rounding
            'begin' => '2020-03-27T14:35:00+1300',
            'end' => '2020-03-28T03:30:00+1300',
            'description' => "**foo**\nbar",
            'duration' => 46500,
            'exported' => true,
            'metaFields' => [],
            'hourlyRate' => 137.21,
            'rate' => 1772.3,
            'internalRate' => 0.0,
        ];

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $result[$key]);
        }
    }

    public function testGetEntityAccessDenied()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_ADMIN);

        $this->assertApiAccessDenied($client, '/api/timesheets/15', 'You are not allowed to view this timesheet');
    }

    public function testGetEntityAccessAllowedForAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
    }

    public function testGetEntityNotFound()
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/20');
    }

    public function testPostAction()
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create(self::TEST_TIMEZONE);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 16 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertTrue($result['duration'] == 57600 || $result['duration'] == 57660); // 1 minute rounding might be applied
        $this->assertEquals(2016, $result['rate']);
    }

    public function testPostActionWithFullExpandedResponse()
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create(self::TEST_TIMEZONE);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 16 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets?full=true', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntityFull', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertTrue($result['duration'] == 57600 || $result['duration'] == 57660); // 1 minute rounding might be applied
        $this->assertEquals(2016, $result['rate']);
    }

    public function testPostActionForDifferentUser()
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create(self::TEST_TIMEZONE);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $admin = $this->getUserByRole(User::ROLE_ADMIN);
        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertNotEquals($admin->getId(), $user->getId());

        $data = [
            'activity' => 1,
            'project' => 1,
            'user' => $user->getId(),
            'begin' => ($dateTime->createDateTime('- 16 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals($user->getId(), $result['user']);
        $this->assertNotEquals($admin->getId(), $result['user']);
    }

    // check for project, as this is a required field. It will not be included in the select, as it is
    // already filtered within the repository due to the hidden customer
    public function testPostActionWithInvisibleProject()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $customer = (new Customer())->setName('foo-bar-1')->setVisible(false)->setCountry('DE')->setTimezone('Europe/Berlin');
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

        $em = $this->getEntityManager();
        $customer = (new Customer())->setName('foo-bar-1')->setVisible(true)->setCountry('DE')->setTimezone('Europe/Berlin');
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
        $dateTime = (new UserDateTimeFactoryFactory($this))->create(self::TEST_TIMEZONE);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importFixtureForUser(User::ROLE_USER);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 7 hours'))->format('Y-m-d\TH:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d\TH:m:0'),
            'description' => 'foo',
            'exported' => true,
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals(25200, $result['duration']);
        $this->assertEquals(1, $result['exported']);
    }

    public function testPatchActionWithInvalidUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole(User::ROLE_TEAMLEAD))
            ->setStartDate(new \DateTime('-10 days'))
            ->setAllowEmptyDescriptions(false)
        ;
        $this->importFixture($fixture);

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
        $this->importFixtureForUser(User::ROLE_USER);

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

    // TODO: TEST PATCH FOR EXPORTED TIMESHEET FOR USER WITHOUT PERMISSION IS REJECTED

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/1');
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testDeleteActionWithUnknownTimesheet()
    {
        $this->assertEntityNotFoundForDelete(User::ROLE_ADMIN, '/api/timesheets/255');
    }

    public function testDeleteActionForDifferentUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);

        $id = 1;

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testDeleteActionWithoutAuthorization()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_ADMIN);

        $this->request($client, '/api/timesheets/15', 'DELETE');

        $response = $client->getResponse();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('You are not allowed to delete this timesheet', $json['message']);
    }

    public function testDeleteActionForExportedRecordIsNotAllowed()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $timesheet->setExported(true);
        $em->persist($timesheet);
        $em->flush();

        $this->request($client, '/api/timesheets/1', 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to delete this timesheet');
    }

    public function testDeleteActionForExportedRecordIsAllowedForAdmin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $timesheet->setExported(true);
        $em->persist($timesheet);
        $em->flush();

        $this->request($client, '/api/timesheets/1', 'DELETE');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testGetRecentAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setStartDate($start)
        ;
        $this->importFixture($fixture);

        $query = [
            'user' => 'all',
            'size' => 2,
            'begin' => $start->format(self::DATE_FORMAT_HTML5),
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets/recent', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollectionFull', $result[0]);
    }

    public function testActiveAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/api/timesheets/active');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $results = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(3, \count($results));
        foreach ($results as $timesheet) {
            self::assertApiResponseTypeStructure('TimesheetCollectionFull', $timesheet);
        }
    }

    public function testStopAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setStartDate($start)
            ->setAmountRunning(1)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/api/timesheets/11/stop', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
    }

    public function testStopActionFailsOnStoppedEntry()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->request($client, '/api/timesheets/1/stop', 'PATCH');

        $this->assertApiException($client->getResponse(), 'Timesheet entry already stopped');
    }

    public function testStopThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/11/stop', []);
    }

    public function testStopNotAllowedForUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(2)
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/api/timesheets/12/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to stop this timesheet');
    }

    public function testGetCollectionWithTags()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(10)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setStartDate(new \DateTime('-10 days'))
            ->setAllowEmptyDescriptions(false)
            ->setUseTags(true)
            ->setTags(['Test', 'Administration']);
        $this->importFixture($fixture);

        $query = ['tags' => 'Test'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(5, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = ['tags' => 'Test,Admin'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(10, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = ['tags' => 'Nothing-2-see,here'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(20, \count($result));
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testRestartAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $data = [
            'description' => 'foo',
            'tags' => 'another,testing,bar'
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));

        $this->request($client, '/api/timesheets/1/restart', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertEmpty($result['description']);
        $this->assertEmpty($result['tags']);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals(1, $timesheet->getActivity()->getId());
        $this->assertEquals(1, $timesheet->getProject()->getId());
        $this->assertEmpty($timesheet->getDescription());
        $this->assertEmpty($timesheet->getTags());
    }

    public function testRestartActionWithBegin()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $data = [
            'description' => 'foo',
            'tags' => 'another,testing,bar'
        ];
        $this->request($client, '/api/timesheets/1', 'PATCH', [], json_encode($data));

        $begin = new \DateTime('2019-11-27 13:55:00');
        $this->request($client, '/api/timesheets/1/restart', 'PATCH', ['begin' => $begin->format(BaseApiController::DATE_FORMAT_PHP)]);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertEmpty($result['description']);
        $this->assertEmpty($result['tags']);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertEquals($begin->format(BaseApiController::DATE_FORMAT_PHP), $timesheet->getBegin()->format(BaseApiController::DATE_FORMAT_PHP));
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals(1, $timesheet->getActivity()->getId());
        $this->assertEquals(1, $timesheet->getProject()->getId());
        $this->assertEmpty($timesheet->getDescription());
        $this->assertEmpty($timesheet->getTags());
    }

    public function testRestartActionWithCopyData()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $timesheet->setDescription('foo');
        $timesheet->addTag((new Tag())->setName('another'));
        $timesheet->addTag((new Tag())->setName('testing'));
        $timesheet->addTag((new Tag())->setName('bar'));
        $timesheet->setMetaField((new TimesheetMeta())->setName('sdfsdf')->setValue('nnnnn')->setIsVisible(true));
        $timesheet->setMetaField((new TimesheetMeta())->setName('xxxxxxx')->setValue('asdasdasd'));
        $timesheet->setMetaField((new TimesheetMeta())->setName('1234567890')->setValue('1234567890')->setIsVisible(true));
        $em->persist($timesheet);
        $em->flush();

        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertEquals('foo', $timesheet->getDescription());

        $this->request($client, '/api/timesheets/1/restart', 'PATCH', ['copy' => 'all']);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertEquals('foo', $result['description']);
        $this->assertEquals([['name' => 'sdfsdf', 'value' => 'nnnnn'], ['name' => '1234567890', 'value' => '1234567890']], $result['metaFields']);
        $this->assertEquals(['another', 'testing', 'bar'], $result['tags']);

        $em = $this->getEntityManager();
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

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(2)
            ->setUser($this->getUserByRole(User::ROLE_ADMIN))
            ->setStartDate($start)
            ->setAmountRunning(3)
        ;
        $this->importFixture($fixture);

        $this->request($client, '/api/timesheets/2/restart', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to re-start this timesheet');
    }

    public function testRestartThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/42/restart', []);
    }

    public function testDuplicateAction()
    {
        $dateTime = (new UserDateTimeFactoryFactory($this))->create(self::TEST_TIMEZONE);
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 16 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $this->request($client, '/api/timesheets', 'POST', [], json_encode($data));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertTrue($result['duration'] == 57600 || $result['duration'] == 57660); // 1 minute rounding might be applied
        $this->assertEquals(2016, $result['rate']);

        $this->request($client, '/api/timesheets/' . $result['id'] . '/duplicate', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertNotEmpty($result['id']);
        $this->assertTrue($result['duration'] == 57600 || $result['duration'] == 57660); // 1 minute rounding might be applied
        $this->assertEquals(2016, $result['rate']);
    }

    public function testDuplicateThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/11/duplicate', []);
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertFalse($timesheet->isExported());

        $this->request($client, '/api/timesheets/1/export', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());
        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $em->clear();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertTrue($timesheet->isExported());

        $this->request($client, '/api/timesheets/1/export', 'PATCH');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $em->clear();
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertFalse($timesheet->isExported());
    }

    public function testExportNotAllowedForUser()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $this->request($client, '/api/timesheets/1/export', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'You are not allowed to lock this timesheet');
    }

    public function testExportThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/42/export', []);
    }

    public function testMetaActionThrowsNotFound()
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/42/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        return $this->assertExceptionForMethod($client, '/api/timesheets/1/meta', 'PATCH', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "name" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        return $this->assertExceptionForMethod($client, '/api/timesheets/1/meta', 'PATCH', ['name' => 'X'], [
            'code' => 400,
            'message' => 'Parameter "value" of value "NULL" violated a constraint "This value should not be null."'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        return $this->assertExceptionForMethod($client, '/api/timesheets/1/meta', 'PATCH', ['name' => 'X', 'value' => 'Y'], [
            'code' => 500,
            'message' => 'Unknown meta-field requested'
        ]);
    }

    public function testMetaAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        static::$container->get('event_dispatcher')->addSubscriber(new TimesheetTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $this->request($client, '/api/timesheets/1/meta', 'PATCH', [], json_encode($data));

        $this->assertTrue($client->getResponse()->isSuccessful());

        $result = json_decode($client->getResponse()->getContent(), true);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        $this->assertEquals(['name' => 'metatestmock', 'value' => 'another,testing,bar'], $result['metaFields'][0]);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertEquals('another,testing,bar', $timesheet->getMetaField('metatestmock')->getValue());
    }
}
