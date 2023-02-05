<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Tests\DataFixtures\ActivityFixtures;
use App\Tests\DataFixtures\TagFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\TimesheetTestMetaFieldSubscriberMock;
use App\Timesheet\DateTimeFactory;

/**
 * @group integration
 */
class TimesheetControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/timesheet/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // there are no records by default in the test database
        $this->assertHasNoEntriesWithFilter($client);
        $this->assertPageActions($client, [
            'download modal-ajax-form' => $this->createUrl('/timesheet/export/'),
            'create modal-ajax-form' => $this->createUrl('/timesheet/create'),
        ]);
    }

    public function testIndexActionWithQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setAmountRunning(2);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange($start, new \DateTime('last day of this month'));

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'state' => 1,
            'pageSize' => 25,
            'daterange' => $dateRange,
            'customers' => [1],
            'projects' => [1],
            'activities' => [1],
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_timesheet', 7);

        // make sure the recording css class exist on tr for targeting running record rows
        $node = $client->getCrawler()->filter('section.content div.datatable_timesheet table.dataTable tbody tr.recording');
        self::assertEquals(2, $node->count());
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $fixture->setCallback(function (Timesheet $timesheet) {
            $timesheet->setDescription('I am a foobar with tralalalala some more content');
            $timesheet->setMetaField((new TimesheetMeta())->setName('location')->setValue('homeoffice'));
            $timesheet->setMetaField((new TimesheetMeta())->setName('feature')->setValue('timetracking'));
        });
        $this->importFixture($fixture);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setAmountRunning(5);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'location:homeoffice foobar',
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_timesheet', 5);
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(15);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setCallback(function (Timesheet $timesheet) {
            $duration = rand(3600, 36000);
            $begin = new \DateTime('-15 days');
            $end = clone $begin;
            $end->modify('+' . $duration . ' seconds');
            $timesheet->setBegin($begin);
            $timesheet->setEnd($end);
        });
        $this->importFixture($fixture);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/export/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange(new \DateTime('-10 days'), new \DateTime());

        $client->submitForm('export-btn-print', [
            'state' => 1,
            'daterange' => $dateRange,
            'customers' => [],
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('body');
        /** @var \DOMElement $body */
        $body = $node->getNode(0);
        $this->assertEquals('invoice_print', $body->getAttribute('class'));

        $result = $node->filter('section.invoice table.table tbody tr');
        $this->assertEquals(5, \count($result));
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'description' => 'Testing is fun!',
                // begin is always pre-filled with the current datetime
                // 'begin' => null,
                // end must be allowed to be null, to start a record
                // there was a bug with end a mandatory field, so we manually set this field to be empty
                'end_time' => null,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals('Testing is fun!', $timesheet->getDescription());
        $this->assertEquals(0, $timesheet->getRate());
        $this->assertNull($timesheet->getHourlyRate());
        $this->assertNull($timesheet->getFixedRate());
    }

    /**
     * @dataProvider getTestDataForDurationValues
     */
    public function testCreateActionWithDurationValues($beginDate, $beginTime, $end, $duration, $expectedDuration, $expectedEnd): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'description' => 'Testing is fun!',
                'begin_date' => $beginDate,
                'begin_time' => $beginTime,
                'end_time' => $end,
                'duration' => $duration,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals($expectedDuration, $timesheet->getDuration());
        $this->assertEquals($expectedEnd, $timesheet->getEnd()->format('Y-m-d H:i:s'));
        $this->assertEquals('Testing is fun!', $timesheet->getDescription());
    }

    public function getTestDataForDurationValues(): \Generator
    {
        // duration is ignored, because end is set and the duration might come from a rounding rule (by default seconds are rounded down with 1)
        yield ['12/31/2018', '12:00 AM', '02:10 AM', '01:00', 7800, '2018-12-31 02:10:00'];
        yield ['12/31/2018', '12:00 AM', '02:09 AM', '01:00', 7740, '2018-12-31 02:09:00'];
        // if seconds are given: they are first rounded up (default for duration rounding is 1)
        yield ['12/31/2018', '12:00 AM', null, '01:00', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '01:00:10', 3660, '2018-12-31 01:01:00'];
        yield ['12/31/2018', '12:00 AM', null, '1h', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '1h10m', 4200, '2018-12-31 01:10:00'];
        yield ['12/31/2018', '12:00 AM', null, '1h10s', 3660, '2018-12-31 01:01:00'];
        yield ['12/31/2018', '12:00 AM', null, '60m', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '60M1s', 3660, '2018-12-31 01:01:00'];
        yield ['12/31/2018', '12:00 AM', null, '3600s', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '59m60s', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '1', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '1,0', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '1.0', 3600, '2018-12-31 01:00:00'];
        yield ['12/31/2018', '12:00 AM', null, '1.5', 5400, '2018-12-31 01:30:00'];
        yield ['12/31/2018', '12:00 AM', null, '1,25', 4500, '2018-12-31 01:15:00'];
    }

    public function testCreateActionShowsMetaFields(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        self::getContainer()->get('event_dispatcher')->addSubscriber(new TimesheetTestMetaFieldSubscriberMock());
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $this->assertTrue($form->has('timesheet_edit_form[metaFields][metatestmock][value]'));
        $this->assertTrue($form->has('timesheet_edit_form[metaFields][foobar][value]'));
        $this->assertFalse($form->has('timesheet_edit_form[metaFields][0][value]'));
    }

    public function testCreateActionDoesNotShowRateFieldsForUser(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $this->assertFalse($form->has('hourlyRate'));
        $this->assertFalse($form->has('fixedRate'));
    }

    public function testCreateActionWithFromAndToValues(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/timesheet/create?from=2018-08-02T20%3A00%3A00&to=2018-08-02T20%3A30%3A00');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(50, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T20:00:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getBegin()->format(\DateTimeInterface::ATOM));

        $expected = new \DateTime('2018-08-02T20:30:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getEnd()->format(\DateTimeInterface::ATOM));
    }

    public function testCreateActionWithFromAndToValuesTwice(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/timesheet/create?from=2018-08-02T20%3A00%3A00&to=2018-08-02T20%3A30%3A00');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(50, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T20:00:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getBegin()->format(\DateTimeInterface::ATOM));

        $expected = new \DateTime('2018-08-02T20:30:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getEnd()->format(\DateTimeInterface::ATOM));

        // create a second entry that is overlapping
        $this->request($client, '/timesheet/create?from=2018-08-02T20%3A02%3A00&to=2018-08-02T20%3A20%3A00');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);
    }

    public function testCreateActionWithFromAndToValuesTwiceFailsOnOverlappingRecord(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/admin/system-config/');

        $form = $client->getCrawler()->filter('form[name=system_configuration_form_timesheet]')->form();
        $client->submit($form, [
            'system_configuration_form_timesheet' => [
                'configuration' => [
                    ['name' => 'timesheet.mode', 'value' => 'default'],
                    ['name' => 'timesheet.default_begin', 'value' => '08:00'],
                    ['name' => 'timesheet.rules.allow_future_times', 'value' => true],
                    ['name' => 'timesheet.rules.allow_zero_duration', 'value' => true],
                    ['name' => 'timesheet.rules.allow_overlapping_records', 'value' => false],
                    ['name' => 'timesheet.rules.allow_overbooking_budget', 'value' => true],
                    ['name' => 'timesheet.active_entries.hard_limit', 'value' => 1],
                ]
            ]
        ]);

        $begin = new \DateTime('2018-08-02T20:00:00');
        $end = new \DateTime('2018-08-02T20:30:00');

        $fixture = new TimesheetFixtures();
        $fixture->setCallback(function (Timesheet $timesheet) use ($begin, $end) {
            $timesheet->setBegin($begin);
            $timesheet->setEnd($end);
        });
        $fixture->setAmount(1);
        $fixture->setUser($this->getUserByRole(User::ROLE_SUPER_ADMIN));
        $this->importFixture($fixture);

        // create a second entry that is overlapping - should fail due to the changed config above
        $this->assertHasValidationError(
            $client,
            '/timesheet/create?from=2018-08-02T20%3A02%3A00&to=2018-08-02T20%3A20%3A00',
            'form[name=timesheet_edit_form]',
            [
                'timesheet_edit_form' => [
                    //'hourlyRate' => 100,
                    'project' => 1,
                    'activity' => 1,
                ]
            ],
            ['#timesheet_edit_form_begin_date']
        );
    }

    public function testCreateActionWithOverbookedActivity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $fixture = new ActivityFixtures();
        $fixture->setAmount(1);
        $fixture->setIsGlobal(true);
        $fixture->setIsVisible(true);
        $fixture->setCallback(function (Activity $activity) {
            $activity->setBudget(1000);
            $activity->setTimeBudget(3600);
        });
        $activities = $this->importFixture($fixture);
        /** @var Activity $activity */
        $activity = $activities[0];

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setActivities([$activity]);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $this->request($client, '/timesheet/' . $id . '/edit');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $this->setSystemConfiguration('timesheet.rules.allow_overbooking_budget', false);

        $this->assertHasValidationError(
            $client,
            '/timesheet/' . $id . '/edit',
            'form[name=timesheet_edit_form]',
            [
                'timesheet_edit_form' => [
                    'hourlyRate' => 100,
                    'begin_date' => '02/18/2020',
                    'begin_time' => '01:00 AM',
                    'end_time' => '02:10 AM',
                    'duration' => '01:10',
                    'project' => 1,
                    'activity' => $activity->getId(),
                ]
            ],
            ['#timesheet_edit_form_activity']
        );
    }

    public function testCreateActionWithEmptyDuration(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $fixture = new ActivityFixtures();
        $fixture->setAmount(1);
        $fixture->setIsGlobal(true);
        $fixture->setIsVisible(true);
        $fixture->setCallback(function (Activity $activity) {
            $activity->setBudget(1000);
            $activity->setTimeBudget(3600);
        });
        $activities = $this->importFixture($fixture);
        /** @var Activity $activity */
        $activity = $activities[0];

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setActivities([$activity]);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $this->request($client, '/timesheet/' . $id . '/edit');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $this->setSystemConfiguration('timesheet.rules.allow_zero_duration', false);

        $this->assertHasValidationError(
            $client,
            '/timesheet/' . $id . '/edit',
            'form[name=timesheet_edit_form]',
            [
                'timesheet_edit_form' => [
                    'hourlyRate' => 100,
                    'begin_date' => '02/18/2020',
                    'begin_time' => '01:00 AM',
                    'end_time' => '01:00 AM',
                    'duration' => '00:00',
                    'project' => 1,
                    'activity' => $activity->getId(),
                ]
            ],
            ['#timesheet_edit_form_duration']
        );
    }

    public function testCreateActionWithBeginAndEndAndTagValues(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/create?begin=2018-08-02&end=2018-08-02&tags=one,two,three');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(800, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T10:00:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getBegin()->format(\DateTimeInterface::ATOM));

        $expected = new \DateTime('2018-08-02T18:00:00');
        $this->assertEquals($expected->format(\DateTimeInterface::ATOM), $timesheet->getEnd()->format(\DateTimeInterface::ATOM));

        $this->assertEquals(['one', 'two', 'three'], $timesheet->getTagsAsArray());
    }

    public function testCreateActionWithDescription(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/timesheet/create?description=Lorem%20Ipsum');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertEquals('Lorem Ipsum', $timesheet->getDescription());
    }

    public function testCreateActionWithDescriptionHtmlInjection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/timesheet/create?description=Some text"><bold>HelloWorld<%2Fbold>');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'hourlyRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->findAll()[0];
        $this->assertEquals('Some text"><bold>HelloWorld</bold>', $timesheet->getDescription());
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser();

        $this->setSystemConfiguration('timesheet.rules.long_running_duration', '1440');

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setFixedStartDate(new \DateTime('-2 hours'));
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/' . $id . '/edit');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $this->assertStringContainsString(
            'href="https://www.kimai.org/documentation/timesheet.html"',
            $response->getContent(),
            'Could not find link to documentation'
        );

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'description' => 'foo-bar',
                'tags' => 'foo,bar, testing, hello world,,',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        $this->assertEquals('foo-bar', $timesheet->getDescription());
    }

    public function testMultiDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->getUserByRole(User::ROLE_USER);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/timesheet/');

        $form = $client->getCrawler()->filter('form[name=multi_update_table]')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/timesheet/multi-delete'));

        $em = $this->getEntityManager();
        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(10, $timesheets);
        $ids = [];
        foreach ($timesheets as $timesheet) {
            $ids[] = $timesheet->getId();
        }

        $client->submit($form, [
            'multi_update_table' => [
                'entities' => implode(',', $ids)
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();

        $em->clear();
        self::assertEquals(0, $em->getRepository(Timesheet::class)->count([]));
    }

    public function testMultiUpdate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $user = $this->getUserByRole(User::ROLE_SUPER_ADMIN);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
        $this->importFixture($fixture);

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/timesheet/');

        $form = $client->getCrawler()->filter('form[name=multi_update_table]')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/timesheet/multi-update'));

        $em = $this->getEntityManager();
        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(10, $timesheets);
        $ids = [];
        foreach ($timesheets as $timesheet) {
            self::assertEmpty($timesheet->getTags());
            self::assertFalse($timesheet->isExported());
            $ids[] = $timesheet->getId();
        }
        // FIXME
        $client->submit($form, [
            'multi_update_table' => [
                'entities' => implode(',', $ids)
            ]
        ]);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_multi_update]')->form();
        $client->submit($form, [
            'timesheet_multi_update' => [
                'exported' => true,
                'tags' => 'test, foo-bar',
                'fixedRate' => 13,
            ]
        ]);

        $em->clear();

        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(10, $timesheets);
        foreach ($timesheets as $timesheet) {
            self::assertCount(2, $timesheet->getTags());
            self::assertTrue($timesheet->isExported());
            self::assertEquals(13, $timesheet->getFixedRate());
        }
    }

    public function testDuplicateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $dateTime = new DateTimeFactory(new \DateTimeZone('Europe/London'));

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(1);
        $fixture->setAmountRunning(0);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($dateTime->createDateTime());
        $fixture->setCallback(function (Timesheet $timesheet) {
            $timesheet->setDescription('Testing is fun!');
            $begin = clone $timesheet->getBegin();
            $begin->setTime(0, 0, 0);
            $timesheet->setBegin($begin);
            $end = clone $timesheet->getBegin();
            $end->modify('+ 8 hours');
            $timesheet->setEnd($end);
            $timesheet->setFixedRate(2016);
            $timesheet->setHourlyRate(127);
        });

        /** @var Timesheet[] $ids */
        $ids = $this->importFixture($fixture);
        $newId = $ids[0]->getId();

        $this->request($client, '/timesheet/' . $newId . '/duplicate');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, $form->getPhpValues());

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($newId++);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertEquals('Europe/London', $timesheet->getBegin()->getTimezone()->getName());
        $this->assertEquals('Testing is fun!', $timesheet->getDescription());
        $this->assertEquals(2016, $timesheet->getRate());
        $this->assertEquals(127, $timesheet->getHourlyRate());
        $this->assertEquals(2016, $timesheet->getFixedRate());
        $this->assertEquals(2016, $timesheet->getRate());
    }
}
