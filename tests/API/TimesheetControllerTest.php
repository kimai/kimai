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
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\TimesheetTestMetaFieldSubscriberMock;
use App\Timesheet\DateTimeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class TimesheetControllerTest extends APIControllerBaseTestCase
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';
    public const DATE_FORMAT_HTML5 = 'Y-m-d\TH:i:s';
    public const TEST_TIMEZONE = 'Europe/London';

    /**
     * @return Timesheet[]
     */
    protected function importFixtureForUser(User|string $user, int $amount = 10): array
    {
        if (\is_string($user)) {
            $role = $user;
            $user = $this->getUserByRole($role);
        }

        $start = DateTimeFactory::createByUser($user)->createDateTime('first day of this month');
        $start = $start->setTime(0, 0, 1);

        $fixture = new TimesheetFixtures($user, $amount);
        $fixture->setFixedRate(true);
        $fixture->setHourlyRate(true);
        $fixture->setAllowEmptyDescriptions(false);
        $fixture->setStartDate($start);

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
        $role = User::ROLE_USER;
        $client = $this->getClientForAuthenticatedUser($role);
        $user = $this->getUserByRole($role);
        $factory = DateTimeFactory::createByUser($user);

        $begin = $factory->createDateTime('first day of this month');
        $begin = $begin->setTime(0, 0, 1);

        $end = $factory->createDateTime('last day of this month');
        $end = $end->setTime(23, 59, 59);

        $modifiedAfter = $factory->createDateTime('-20 hour');

        $query = [
            'customers' => ['1'],
            'projects' => ['1'],
            'activities' => ['1'],
            'page' => '2',
            'size' => '4',
            'order' => 'DESC',
            'orderBy' => 'rate',
            'active' => 0,
            'modified_after' => $modifiedAfter->format(self::DATE_FORMAT_HTML5),
            'begin' => $begin->format(self::DATE_FORMAT_HTML5),
            'end' => $end->format(self::DATE_FORMAT_HTML5),
            'exported' => 0,
        ];

        $this->importFixtureForUser($user, 22);
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
        $query = [
            'page' => 19,
            'size' => 50,
        ];

        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->importFixtureForUser(User::ROLE_USER);
        $this->request($client, '/api/timesheets', 'GET', $query);
        $this->assertApiException($client->getResponse(), ['code' => Response::HTTP_NOT_FOUND, 'message' => 'Not Found']);
    }

    public function testGetCollectionWithSingleParamsQuery(): void
    {
        $role = User::ROLE_USER;
        $client = $this->getClientForAuthenticatedUser($role);
        $user = $this->getUserByRole($role);
        $factory = DateTimeFactory::createByUser($user);

        $begin = $factory->create('first day of this month');
        $begin = $begin->setTime(0, 0, 1);

        $end = $factory->create('last day of this month');
        $end = $end->setTime(23, 59, 59);

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

        $this->importFixtureForUser($user);
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
        $role = User::ROLE_USER;
        $client = $this->getClientForAuthenticatedUser($role);
        $user = $this->getUserByRole($role);
        $factory = DateTimeFactory::createByUser($user);
        $this->importFixtureForUser($user);

        $fixture = new TimesheetFixtures($user, 7);
        $fixture->setExported(true);
        $fixture->setStartDate(new \DateTime('first day of this month'));
        $fixture->setAllowEmptyDescriptions(false);
        $this->importFixture($fixture);

        $begin = $factory->create('first day of this month');
        $begin = $begin->setTime(0, 0, 1);

        $end = $factory->create('last day of this month');
        $end = $end->setTime(23, 59, 59);

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
            'duration' => 46500, // 12,916 => rounded 12,92 * 137,21 = 46512
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

    #[DataProvider('getTrackingModeTestData')]
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
            'end' => (new \DateTime('-1 day'))->format('Y-m-d H:m'),
            'description' => 'foo',
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets/' . $timesheets[0]->getId(), 'PATCH', [], $json);

        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        $this->assertApiCallValidationError($response, ['activity'], false, ['The end date must not be earlier than the start date.']);
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
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingValue(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->assertExceptionForMethod($client, '/api/timesheets/' . $id . '/meta', 'PATCH', ['name' => 'X'], [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    public function testMetaActionThrowsExceptionOnMissingMetafield(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $timesheets = $this->importFixtureForUser(User::ROLE_USER);
        $id = $timesheets[0]->getId();

        $this->assertExceptionForMethod($client, '/api/timesheets/' . $id . '/meta', 'PATCH', ['name' => 'X', 'value' => 'Y'], [
            'code' => Response::HTTP_NOT_FOUND,
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

    // ------------------------------------------------------------------
    // CVE-2024-29200 / GHSA-cj3c-5xpm-cx94 — per-record IDOR regression suite.
    //
    // The list endpoint fix (TimesheetRepository::addPermissionCriteria) covers
    // GET /api/timesheets only. The per-record routes load a Timesheet by id
    // and rely entirely on TimesheetVoter for authorisation. Previously
    // the voter only asked "is this the caller's own entry?" — so a teamlead
    // with view_other_timesheet could read, mutate or delete any timesheet by
    // id, regardless of team scope. These tests pin the new team-scoped
    // behaviour (RolePermissionManager::checkTeamAccessTimesheet) on every
    // affected route.
    // ------------------------------------------------------------------

    public function testCveIdorTeamleadCannotReachAnyPerRecordRouteWhenCustomerTeamRestricts(): void
    {
        // Direct reproduction of the security advisory's PoC. The customer
        // belongs to a team the teamlead is not in; the timesheet owner is
        // a different user; the teamlead must be denied on every per-record
        // route, not just on GET /api/timesheets.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);
        $em->persist($ownerTeam);

        $customerTeam = new Team('customer team');
        $customerTeam->addUser($owner);
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        // 1) GET /api/timesheets/{id}
        $this->assertApiAccessDenied($client, '/api/timesheets/' . $id);

        // 2) PATCH /api/timesheets/{id}
        $patch = json_encode(['description' => 'HIJACKED_BY_BOB']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 3) PATCH /api/timesheets/{id}/stop  and  4) GET .../stop
        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());
        $this->request($client, '/api/timesheets/' . $id . '/stop', 'GET');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 5) PATCH /api/timesheets/{id}/restart  and  6) GET .../restart
        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());
        $this->request($client, '/api/timesheets/' . $id . '/restart', 'GET');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 7) PATCH /api/timesheets/{id}/duplicate
        $this->request($client, '/api/timesheets/' . $id . '/duplicate', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 8) PATCH /api/timesheets/{id}/export
        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 9) PATCH /api/timesheets/{id}/meta
        $meta = json_encode(['name' => 'metatestmock', 'value' => 'pwned']);
        self::assertIsString($meta);
        $this->request($client, '/api/timesheets/' . $id . '/meta', 'PATCH', [], $meta);
        $this->assertApiResponseAccessDenied($client->getResponse());

        // 10) DELETE /api/timesheets/{id}  — verified last because it is destructive.
        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // The timesheet must still be in the database after every attempted attack.
        self::assertNotNull(
            $this->getEntityManager()->getRepository(Timesheet::class)->find($id),
            'PoC: DELETE leaked through and the row was removed from the database.'
        );
    }

    public function testCveIdorTeamleadAsPlainMemberOfOwnerTeamCannotReachAnyPerRecordRoute(): void
    {
        // No customer/project/activity team restriction — only the owner is in
        // a team. The teamlead is a plain member of that same team. Plain
        // membership must NOT be enough to reach a foreign user's timesheet
        // via per-record routes (RolePermissionManager::checkTeamLeadAccess).
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addUser($teamlead); // plain member, not addTeamlead()
        $em->persist($sharedTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        $this->assertApiAccessDenied($client, '/api/timesheets/' . $id);

        $patch = json_encode(['description' => 'HIJACKED_BY_BOB']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id . '/restart', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id . '/duplicate', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $meta = json_encode(['name' => 'metatestmock', 'value' => 'pwned']);
        self::assertIsString($meta);
        $this->request($client, '/api/timesheets/' . $id . '/meta', 'PATCH', [], $meta);
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse());
    }

    public function testTeamleadOfOwnerTeamCanAccessTimesheetOnPerRecordRoutes(): void
    {
        // Positive control: when the teamlead is actually the teamlead of the
        // owner's team and there is no customer/project/activity restriction,
        // they pass the new team gate.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $sharedTeam = new Team('shared');
        $sharedTeam->addUser($owner);
        $sharedTeam->addTeamlead($teamlead);
        $em->persist($sharedTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        // GET — read access succeeds.
        $this->assertAccessIsGranted($client, '/api/timesheets/' . $id);

        // PATCH — mutation succeeds.
        $patch = json_encode(['description' => 'edited by teamlead']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        self::assertTrue($client->getResponse()->isSuccessful(), 'PATCH should succeed when teamlead is teamlead of owner team');

        // /duplicate — succeeds (project + activity visible).
        $this->request($client, '/api/timesheets/' . $id . '/duplicate', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful(), 'duplicate should succeed for legitimate teamlead');

        // /export — succeeds, ROLE_TEAMLEAD has edit_export_other_timesheet.
        $this->request($client, '/api/timesheets/' . $id . '/export', 'PATCH');
        self::assertTrue($client->getResponse()->isSuccessful(), 'export should succeed for legitimate teamlead');
    }

    public function testCustomerTeamRestrictionStillBlocksLegitimateTeamleadOfOwnerTeam(): void
    {
        // Even with the teamlead being the teamlead of the owner's team, the
        // customer-level team gate must still apply. A teamlead may not bypass
        // a customer's team restriction just because they happen to lead the
        // owner's team.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);
        $ownerTeam->addTeamlead($teamlead);
        $em->persist($ownerTeam);

        // Customer team has only the owner; the teamlead is NOT a member.
        $customerTeam = new Team('customer team');
        $customerTeam->addUser($owner);
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        $this->assertApiAccessDenied($client, '/api/timesheets/' . $id);

        $patch = json_encode(['description' => 'should not work']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse());
    }

    public function testOwnerCanAlwaysAccessOwnTimesheetEvenWithRestrictiveTeams(): void
    {
        // Owner short-circuit: the team gate must NOT apply when the caller is
        // also the timesheet's user. Even a customer team locked to other
        // users plus an owner-only team must not prevent self-access.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);

        $customerTeam = new Team('customer team excluding owner');
        // owner is NOT a member of the customer team — checkTeamAccessProject
        // would normally deny. Owner short-circuit must bypass it.
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        $this->assertAccessIsGranted($client, '/api/timesheets/' . $id);

        $patch = json_encode(['description' => 'self edit']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        self::assertTrue($client->getResponse()->isSuccessful(), 'Owner must be able to edit own timesheet');
    }

    public function testSuperAdminCanAccessTimesheetDespiteRestrictiveTeams(): void
    {
        // canSeeAllData via isSuperAdmin() — bypasses every team gate, on
        // every per-record route.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);

        $ownerTeam = new Team('owner team');
        $ownerTeam->addUser($owner);
        $em->persist($ownerTeam);

        $customerTeam = new Team('customer team excluding super admin');
        $customerTeam->addUser($owner);
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        $this->assertAccessIsGranted($client, '/api/timesheets/' . $id);

        $patch = json_encode(['description' => 'super admin edit']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        self::assertTrue($client->getResponse()->isSuccessful(), 'SUPER_ADMIN must be able to edit any timesheet');

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
    }

    public function testCveIdorOnRunningTimesheetStopRoutesAreBlocked(): void
    {
        // /stop targets a running timesheet. Verifies that even when the route
        // would otherwise be functional, the team gate denies access.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);

        $customerTeam = new Team('customer team');
        $customerTeam->addUser($owner);
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: true);
        $id = $timesheet->getId();
        self::assertIsInt($id);
        self::assertNull($timesheet->getEnd(), 'sanity: timesheet must be running for /stop');

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'PATCH');
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id . '/stop', 'GET');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // Confirm side-effect-free: timesheet must still be running.
        $em->clear();
        $reloaded = $em->getRepository(Timesheet::class)->find($id);
        self::assertInstanceOf(Timesheet::class, $reloaded);
        self::assertNull($reloaded->getEnd(), '/stop must not have stopped the timesheet behind the team gate');
    }

    public function testTeamleadFromUnrelatedTeamCannotAccessTimesheetById(): void
    {
        // Mirrors the "bob-from-TeamB attacks alice-in-TeamA" PoC from the
        // advisory: both users have teams, but those teams are completely
        // unrelated. The attacker happens to be a teamlead of his own team.
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $em = $this->getEntityManager();
        $owner = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);

        $teamA = new Team('TeamA — owner only');
        $teamA->addUser($owner);

        $teamB = new Team('TeamB — attacker only');
        $teamB->addTeamlead($teamlead);

        $customerTeam = new Team('customer team — TeamA scope');
        $customerTeam->addUser($owner);

        $em->persist($teamA);
        $em->persist($teamB);
        $em->persist($customerTeam);

        $timesheet = $this->persistRestrictedTimesheet($owner, [$customerTeam], running: false);
        $id = $timesheet->getId();
        self::assertIsInt($id);

        $this->assertApiAccessDenied($client, '/api/timesheets/' . $id);

        $patch = json_encode(['description' => 'HIJACKED_BY_BOB']);
        self::assertIsString($patch);
        $this->request($client, '/api/timesheets/' . $id, 'PATCH', [], $patch);
        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->request($client, '/api/timesheets/' . $id, 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse());

        // Description must not have been mutated.
        $em->clear();
        $reloaded = $em->getRepository(Timesheet::class)->find($id);
        self::assertInstanceOf(Timesheet::class, $reloaded);
        self::assertSame('ALICE_SECRET', $reloaded->getDescription(), 'PATCH leaked through and rewrote the description.');
    }

    /**
     * @param list<Team> $customerTeams teams to attach to the customer (= the project's customer)
     */
    private function persistRestrictedTimesheet(User $owner, array $customerTeams, bool $running): Timesheet
    {
        $em = $this->getEntityManager();

        $customer = new Customer('CVE-2024-29200 customer');
        $customer->setCountry('DE');
        $customer->setTimezone(self::TEST_TIMEZONE);
        $customer->setVisible(true);
        foreach ($customerTeams as $team) {
            $customer->addTeam($team);
        }
        $em->persist($customer);

        $project = new Project();
        $project->setName('CVE-2024-29200 project');
        $project->setCustomer($customer);
        $project->setVisible(true);
        $em->persist($project);

        $activity = new Activity();
        $activity->setName('CVE-2024-29200 activity');
        $activity->setProject($project);
        $activity->setVisible(true);
        $em->persist($activity);

        // Flush the catalog entities first so they have ids before any Doctrine
        // subscriber tries to query them while persisting the timesheet
        // (RateService re-loads the activity inside the timesheet onFlush hook).
        $em->flush();

        $timesheet = new Timesheet();
        $timesheet->setUser($owner);
        $timesheet->setProject($project);
        $timesheet->setActivity($activity);
        $timesheet->setBegin(new \DateTime('-2 hours'));
        $timesheet->setDescription('ALICE_SECRET');
        if (!$running) {
            $end = new \DateTime('-1 hour');
            $timesheet->setEnd($end);
            $timesheet->setDuration(3600);
        }
        $em->persist($timesheet);
        $em->flush();

        return $timesheet;
    }
}
