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
use App\Tests\Mocks\TimesheetTestMetaFieldSubscriberMock;
use App\Timesheet\DateTimeFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group integration
 */
class TimesheetControllerTest extends APIControllerBaseTestCase
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';
    public const DATE_FORMAT_HTML5 = 'Y-m-d\TH:i:s';
    public const TEST_TIMEZONE = 'Europe/London';

    /**
     * @return Timesheet[]
     */
    protected function importFixtureForUser(string $role, int $amount = 10): array
    {
        $fixture = new TimesheetFixtures($this->getUserByRole($role), $amount);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setAllowEmptyDescriptions(false);
        $fixture->setStartDate((new \DateTime('first day of this month'))->setTime(0, 0, 1));

        return $this->importFixture($fixture);
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/timesheets');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionFull(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', ['full' => 'true']);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollectionFull', $result[0]);
    }

    public function testGetCollectionForOtherUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_ADMIN), 7);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $query = ['user' => 2];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = ['users' => [2]];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionForAllUserIsSecure(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_ADMIN), 7);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $query = ['user' => 'all'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEmpty($result);
        self::assertEquals(0, \count($result));
    }

    public function testGetCollectionForAllUserIsSecureForUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_ADMIN), 7);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $query = ['user' => 'all'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionForAllUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_SUPER_ADMIN), 7);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $query = ['user' => 'all'];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(17, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionForEmptyResult(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $this->assertAccessIsGranted($client, '/api/timesheets');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function testGetCollectionWithQuery(): void
    {
        $modifiedAfter = new \DateTime('-1 hour');
        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'customers' => ['1'],
            'projects' => ['1'],
            'activities' => ['1'],
            'page' => 2,
            'size' => 4,
            'order' => 'DESC',
            'orderBy' => 'rate',
            'active' => 0,
            'modified_after' => $modifiedAfter->format(self::DATE_FORMAT_HTML5),
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER, 22);
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        $this->assertPagination($client->getResponse(), 2, 4, 6, 22);
        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(4, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetCollectionWithQueryFailsWith404OnOutOfRangedPage(): void
    {
        $begin = new \DateTime('first day of this month');
        $begin->setTime(0, 0, 0);
        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $query = [
            'page' => 19,
            'size' => 50,
        ];

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->request($client, '/api/timesheets', 'GET', $query);
        $this->assertApiException($client->getResponse(), ['code' => 404, 'message' => 'Not Found']);
    }

    public function testGetCollectionWithSingleParamsQuery(): void
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
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(5, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testExportedFilter(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_USER), 7);
        $fixture->setExported(true);
        $fixture->setStartDate(new \DateTime('first day of this month'));
        $fixture->setAllowEmptyDescriptions(false);
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
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(7, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = [
            'page' => 1,
            'size' => 50,
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
        ];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(17, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);
    }

    public function testGetEntity(): void
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
        $em->flush();

        $this->assertAccessIsGranted($client, '/api/timesheets/' . $timesheet->getId());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $expected = [
            'activity' => 1,
            'project' => 1,
            'user' => 2,
            'tags' => [
                0 => 'test'
            ],
            // make sure the timezone is properly applied in serializer (see #1858)
            // minute and second are different from the above datetime object, because of applied default minute rounding
            'begin' => '2020-03-27T14:35:00+1300',
            'end' => '2020-03-28T03:30:00+1300',
            'description' => "**foo**\nbar",
            'duration' => 46500,
            'exported' => true,
            'metaFields' => [],
            'hourlyRate' => 137.21,
            'rate' => 1772.2958,
            'internalRate' => 1772.2958,
        ];

        foreach ($expected as $key => $value) {
            self::assertEquals($value, $result[$key], \sprintf('Field %s has invalid value', $key));
        }
    }

    public function testGetEntityAccessDenied(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_ADMIN);
        self::assertCount(10, $timesheets);

        $this->assertApiAccessDenied($client, '/api/timesheets/' . $timesheets[0]->getId(), 'Access denied.');
    }

    public function testGetEntityAccessAllowedForAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/' . $timesheets[0]->getId());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
    }

    public function testGetEntityNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/timesheets/' . PHP_INT_MAX);
    }

    public function testPostAction(): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('-8 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127,
            'billable' => false
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertTrue($result['duration'] == 28800 || $result['duration'] == 28860); // 1 minute rounding might be applied
        self::assertEquals(2016, $result['rate']);
        self::assertFalse($result['billable']);
    }

    public function testPostActionWithFullExpandedResponse(): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('-8 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127,
            'billable' => true
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets?full=true', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetExpanded', $result);
        self::assertNotEmpty($result['id']);
        self::assertTrue($result['duration'] == 28800 || $result['duration'] == 28860); // 1 minute rounding might be applied
        self::assertEquals(2016, $result['rate']);
        self::assertTrue($result['billable']);
    }

    public function testPostActionForDifferentUser(): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $admin = $this->getUserByRole(User::ROLE_ADMIN);
        $user = $this->getUserByRole(User::ROLE_USER);

        self::assertNotEquals($admin->getId(), $user->getId());

        $data = [
            'activity' => 1,
            'project' => 1,
            'user' => $user->getId(),
            'begin' => ($dateTime->createDateTime('- 8 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals($user->getId(), $result['user']);
        $this->assertNotEquals($admin->getId(), $result['user']);
        self::assertTrue($result['billable']);
    }

    // check for project, as this is a required field. It will not be included in the select, as it is
    // already filtered within the repository due to the hidden customer
    public function testPostActionWithInvisibleProject(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $customer = new Customer('foo-bar-1');
        $customer->setVisible(false);
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);
        $project = new Project();
        $project->setName('foo-bar-2');
        $project->setVisible(true);
        $project->setCustomer($customer);
        $em->persist($project);
        $activity = new Activity();
        $activity->setName('foo-bar-3');
        $activity->setVisible(true);
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m:s'),
            'end' => (new \DateTime())->format('Y-m-d H:m:s'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        $this->assertApiCallValidationError($client->getResponse(), ['project']);
    }

    public function testPostActionWithUnknownActivity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $customer = new Customer('foo-bar-1');
        $customer->setVisible(false);
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);
        $project = new Project();
        $project->setName('foo-bar-2');
        $project->setVisible(true);
        $project->setCustomer($customer);
        $em->persist($project);

        $data = [
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m:s'),
            'end' => (new \DateTime())->format('Y-m-d H:m:s'),
            'project' => $project->getId(),
            'activity' => 99,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        $this->assertApiCallValidationError($client->getResponse(), ['project']);
    }

    public function testPostActionWithNonExistingProject(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $activity = new Activity();
        $activity->setName('foo-bar-3');
        $activity->setVisible(true);
        $em->persist($activity);
        $em->flush();

        $data = [
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m:s'),
            'end' => (new \DateTime())->format('Y-m-d H:m:s'),
            'project' => 99,
            'activity' => $activity->getId(),
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        $this->assertApiCallValidationError($client->getResponse(), ['project']);
    }

    // check for activity, as this is a required field. It will not be included in the select, as it is
    // already filtered within the repository due to the hidden flag
    public function testPostActionWithInvisibleActivity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        $customer = new Customer('foo-bar-1');
        $customer->setVisible(true);
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $em->persist($customer);
        $project = new Project();
        $project->setName('foo-bar-2');
        $project->setVisible(true);
        $project->setCustomer($customer);
        $em->persist($project);
        $activity = new Activity();
        $activity->setName('foo-bar-3');
        $activity->setVisible(false);
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        $this->assertApiCallValidationError($client->getResponse(), ['activity']);
    }

    public function testPostActionWithNonBillableCustomer(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $em = $this->getEntityManager();
        $customer = new Customer('foo-bar-1');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $customer->setBillable(false);
        $em->persist($customer);
        $project = new Project();
        $project->setName('foo-bar-2');
        $project->setCustomer($customer);
        $em->persist($project);
        $activity = new Activity();
        $activity->setName('foo-bar-3');
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertFalse($result['billable']);
    }

    public function testPostActionWithNonBillableCustomerExplicit(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $em = $this->getEntityManager();
        $customer = new Customer('foo-bar-1');
        $customer->setCountry('DE');
        $customer->setTimezone('Europe/Berlin');
        $customer->setBillable(false);
        $em->persist($customer);
        $project = new Project();
        $project->setName('foo-bar-2');
        $project->setCustomer($customer);
        $em->persist($project);
        $activity = new Activity();
        $activity->setName('foo-bar-3');
        $em->persist($activity);
        $em->flush();

        $data = [
            'activity' => $activity->getId(),
            'project' => $project->getId(),
            'begin' => (new \DateTime('- 8 hours'))->format('Y-m-d H:m'),
            'end' => (new \DateTime())->format('Y-m-d H:m'),
            'description' => 'foo',
            'billable' => true,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        // explicit overwritten values win!
        self::assertTrue($result['billable']);
    }

    public static function getTrackingModeTestData(): array
    {
        return [
            ['duration_fixed_begin', User::ROLE_USER, false],
            ['duration_fixed_begin', User::ROLE_ADMIN, true],
            ['duration_fixed_begin', User::ROLE_SUPER_ADMIN, true],
            ['punch', User::ROLE_USER, false],
            ['punch', User::ROLE_ADMIN, true],
            ['punch', User::ROLE_SUPER_ADMIN, true],
            ['default', User::ROLE_USER, true],
            ['default', User::ROLE_ADMIN, true],
            ['default', User::ROLE_SUPER_ADMIN, true]
        ];
    }

    /**
     * @dataProvider getTrackingModeTestData
     */
    public function testCreateActionWithTrackingModeHasFieldsForUser(string $trackingMode, string $user, bool $showTimes): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser($user);
        $this->setSystemConfiguration('timesheet.mode', $trackingMode);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('-8 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        $response = $client->getResponse();

        if ($showTimes) {
            self::assertTrue($response->isSuccessful());
        } else {
            $this->assertApiCallValidationError($response, [], true, [], [], ['begin', 'end']);
        }
    }

    public function testPatchAction(): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 7 hours'))->format('Y-m-d\TH:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d\TH:m:0'),
            'description' => 'foo',
            'billable' => false,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'PATCH', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertEquals(25200, $result['duration']);
        self::assertEquals('foo', $result['description']);
        self::assertFalse($result['billable']);
    }

    public function testPatchActionWithInvalidUser(): void
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
        $timesheets = $this->importFixture($fixture);

        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => (new \DateTime('- 7 hours'))->format('Y-m-d\TH:m:s'),
            'end' => (new \DateTime())->format('Y-m-d\TH:m:s'),
            'description' => 'foo',
            'exported' => true,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'PATCH', [], $json);
        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testPatchActionWithUnknownTimesheet(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/255', []);
    }

    public function testInvalidPatchAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);

        $data = [
            'activity' => 10,
            'project' => 1,
            'begin' => (new \DateTime())->format('Y-m-d H:m'),
            'end' => (new \DateTime('- 1 hours'))->format('Y-m-d H:m'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'PATCH', [], $json);

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['activity'], false, ['End date must not be earlier then start date.']);
    }

    // TODO: TEST PATCH FOR EXPORTED TIMESHEET FOR USER WITHOUT PERMISSION IS REJECTED

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $this->assertAccessIsGranted($client, '/api/timesheets/' . $timesheets[0]->getId());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertIsNumeric($result['id']);
        $id = $result['id'];

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());
    }

    public function testDeleteActionWithUnknownTimesheet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/timesheets/255');
    }

    public function testDeleteActionForDifferentUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);

        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());
    }

    public function testDeleteActionWithoutAuthorization(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_ADMIN);

        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'DELETE');

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteActionForExportedRecordIsNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $id = $timesheet->getId();
        $timesheet->setExported(true);
        $em->persist($timesheet);
        $em->flush();

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteActionForExportedRecordIsAllowedForAdmin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->importFixtureForUser(User::ROLE_USER);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $id = $timesheet->getId();
        $timesheet->setExported(true);
        $em->persist($timesheet);
        $em->flush();

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        self::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testGetRecentAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

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
            'size' => 2,
            'begin' => $start->format(self::DATE_FORMAT_HTML5),
        ];

        $this->assertAccessIsGranted($client, '/api/timesheets/recent', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(1, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollectionFull', $result[0]);
    }

    public function testActiveAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $start = new \DateTime('-10 days');

        $fixture = new TimesheetFixtures($this->getUserByRole(User::ROLE_USER));
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setStartDate($start);
        $fixture->setAmountRunning(3);
        $this->importFixture($fixture);

        $this->request($client, '/api/timesheets/active');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertEquals(3, \count($result));
        foreach ($result as $timesheet) {
            self::assertIsArray($timesheet);
            self::assertApiResponseTypeStructure('TimesheetCollectionFull', $timesheet);
        }
    }

    public function testStopAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);

        $start = new \DateTime('-4 hours');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setFixedStartDate($start)
            ->setAmountRunning(1)
        ;
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertInstanceOf(\DateTime::class, $timesheet->getEnd());
    }

    public function testStopActionTriggersValidationOnLongRunning(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->setSystemConfiguration('timesheet.rules.long_running_duration', 750);
        $this->importFixtureForUser(User::ROLE_USER);

        $start = new \DateTime('-13 hours');

        $fixture = new TimesheetFixtures();
        $fixture
            ->setFixedRate(true)
            ->setHourlyRate(true)
            ->setAmount(0)
            ->setUser($this->getUserByRole(User::ROLE_USER))
            ->setFixedStartDate($start)
            ->setAmountRunning(1)
        ;
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        $this->assertApiCallValidationError($client->getResponse(), ['duration' => 'Maximum 12:30 hours allowed.']);
    }

    public function testStopThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/11/stop', []);
    }

    public function testStopNotAllowedForUser(): void
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
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[3]->getId();

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');
    }

    public function testGetCollectionWithTags(): void
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
            ->setTags(['Test', 'Administration']);
        $this->importFixture($fixture);

        $query = ['tags' => ['Test']];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = ['tags' => ['Test', 'Admin']];
        $this->assertAccessIsGranted($client, '/api/timesheets', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('TimesheetCollection', $result[0]);

        $query = ['tags' => ['Nothing-2-see', 'not-existing-here']];
        $this->request($client, '/api/timesheets', 'GET', $query);
        $this->assertBadRequestResponse($client->getResponse());
    }

    public function testRestartAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $data = [
            'description' => 'foo',
            'tags' => ['another', 'testing', 'bar']
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $json);

        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertEmpty($result['description']);
        self::assertEmpty($result['tags']);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        self::assertNull($timesheet->getEnd());
        self::assertEquals(1, $timesheet->getActivity()->getId());
        self::assertEquals(1, $timesheet->getProject()->getId());
        self::assertEmpty($timesheet->getDescription());
        self::assertEmpty($timesheet->getTags());
    }

    public function testRestartActionWithBegin(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $data = [
            'description' => 'foo',
            'tags' => ['another', 'testing', 'bar']
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $json);

        $begin = new \DateTime('2019-11-27 13:55:00');
        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH', ['begin' => $begin->format(BaseApiController::DATE_FORMAT_PHP)]);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertEmpty($result['description']);
        self::assertEmpty($result['tags']);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        self::assertEquals($begin->format(BaseApiController::DATE_FORMAT_PHP), $timesheet->getBegin()->format(BaseApiController::DATE_FORMAT_PHP));
        self::assertNull($timesheet->getEnd());
        self::assertEquals(1, $timesheet->getActivity()->getId());
        self::assertEquals(1, $timesheet->getProject()->getId());
        self::assertEmpty($timesheet->getDescription());
        self::assertEmpty($timesheet->getTags());
    }

    public function testRestartActionWithCopyData(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        $timesheet->setDescription('foo');
        $timesheet->addTag((new Tag())->setName('another'));
        $timesheet->addTag((new Tag())->setName('testing'));
        $timesheet->addTag((new Tag())->setName('bar'));
        $timesheet->setMetaField((new TimesheetMeta())->setName('sdfsdf')->setValue('nnnnn')->setIsVisible(true));
        $timesheet->setMetaField((new TimesheetMeta())->setName('xxxxxxx')->setValue('asdasdasd'));
        $timesheet->setMetaField((new TimesheetMeta())->setName('1234567890')->setValue('1234567890')->setIsVisible(true));
        $em->persist($timesheet);
        $em->flush();

        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertEquals('foo', $timesheet->getDescription());

        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH', ['copy' => 'all']);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertEquals('foo', $result['description']);
        self::assertEquals([['name' => 'sdfsdf', 'value' => 'nnnnn'], ['name' => '1234567890', 'value' => '1234567890']], $result['metaFields']);
        self::assertEquals(['another', 'testing', 'bar'], $result['tags']);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($result['id']);
        self::assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        self::assertNull($timesheet->getEnd());
        self::assertEquals(1, $timesheet->getActivity()->getId());
        self::assertEquals(1, $timesheet->getProject()->getId());
        self::assertEquals('foo', $timesheet->getDescription());
        self::assertEquals(['another', 'testing', 'bar'], $timesheet->getTagsAsArray());
    }

    public function testRestartNotAllowedForUser(): void
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
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');
    }

    public function testRestartThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_USER, '/api/timesheets/42/restart', []);
    }

    public function testDuplicateAction(): void
    {
        $dateTime = new DateTimeFactory(new \DateTimeZone(self::TEST_TIMEZONE));
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => 1,
            'project' => 1,
            'begin' => ($dateTime->createDateTime('- 8 hours'))->format('Y-m-d H:m:0'),
            'end' => ($dateTime->createDateTime())->format('Y-m-d H:m:0'),
            'description' => 'foo',
            'fixedRate' => 2016,
            'hourlyRate' => 127
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertTrue($result['duration'] == 28800 || $result['duration'] == 28860); // 1 minute rounding might be applied
        self::assertEquals(2016, $result['rate']);
        $id = $result['id'];
        self::assertIsNumeric($id);
        $this->request($client, '/api/timesheets/' . $id . '/duplicate', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertNotEmpty($result['id']);
        self::assertTrue($result['duration'] == 28800 || $result['duration'] == 28860); // 1 minute rounding might be applied
        self::assertEquals(2016, $result['rate']);
    }

    public function testDuplicateThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/11/duplicate', []);
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertFalse($timesheet->isExported());

        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);

        $em->clear();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertTrue($timesheet->isExported());

        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful());

        $em->clear();
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertFalse($timesheet->isExported());
    }

    public function testExportNotAllowedForUser(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse(), 'Access denied.');
    }

    public function testExportThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/' . PHP_INT_MAX . '/export', []);
    }

    public function testMetaActionThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/timesheets/' . PHP_INT_MAX . '/meta', []);
    }

    public function testMetaActionThrowsExceptionOnMissingName(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->assertExceptionForMethod($client, '/api/timesheets/' . $id . '/meta', 'PATCH', ['value' => 'X'], [
            'code' => 400,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->assertExceptionForMethod($client, '/api/timesheets/' . $id . '/meta', 'PATCH', ['name' => 'X'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->assertExceptionForMethod($client, '/api/timesheets/' . $id . '/meta', 'PATCH', ['name' => 'X', 'value' => 'Y'], [
            'code' => 404,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new TimesheetTestMetaFieldSubscriberMock());

        $data = [
            'name' => 'metatestmock',
            'value' => 'another,testing,bar'
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $id . '/meta', 'PATCH', [], $json);

        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('TimesheetEntity', $result);
        self::assertEquals(['name' => 'metatestmock', 'value' => 'another,testing,bar'], $result['metaFields'][0]);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        self::assertEquals('another,testing,bar', $timesheet->getMetaField('metatestmock')->getValue());
    }
}
