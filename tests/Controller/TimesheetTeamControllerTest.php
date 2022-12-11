<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Tests\DataFixtures\TagFixtures;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Timesheet\DateTimeFactory;
use App\Timesheet\Util;

/**
 * @group integration
 */
class TimesheetTeamControllerTest extends ControllerBaseTest
{
    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/team/timesheet/');
    }

    public function testIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_USER, '/team/timesheet/');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->assertAccessIsGranted($client, '/team/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // there are no records by default in the test database
        $this->assertHasNoEntriesWithFilter($client);

        $this->assertPageActions($client, [
            'download modal-ajax-form' => $this->createUrl('/team/timesheet/export/'),
            'create create-ts modal-ajax-form' => $this->createUrl('/team/timesheet/create'),
            'multi-user create-ts-mu modal-ajax-form' => $this->createUrl('/team/timesheet/create_mu'),
        ]);
    }

    public function testIndexActionWithQuery(): void
    {
        // Switching the user is not allowed for TEAMLEADs but ONLLY for admin and super-admins
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $start = new \DateTime('first day of this month');

        $user = $this->getUserByRole(User::ROLE_USER);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setAmountRunning(3);
        $fixture->setUser($user);
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/team/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = $this->formatDateRange($start, new \DateTime('last day of this month'));

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'state' => 1,
            'users' => [$user->getId()],
            'pageSize' => 25,
            'daterange' => $dateRange,
            'customers' => [],
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_timesheet_admin', 13);

        // make sure the recording css class exist on tr for targeting running record rows
        $node = $client->getCrawler()->filter('section.content div.datatable_timesheet_admin table.dataTable tbody tr.recording');
        self::assertEquals(3, $node->count());
    }

    public function testIndexActionWithSearchTermQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
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

        $this->request($client, '/team/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form.searchform')->form();
        $client->submit($form, [
            'searchTerm' => 'location:homeoffice foobar',
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_timesheet_admin', 5);
    }

    public function testExportAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TimesheetFixtures();
        $fixture->setAmount(7);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(3);
        $fixture->setUser($this->getUserByRole(User::ROLE_TEAMLEAD));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $this->request($client, '/team/timesheet/export/');
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
        $this->assertEquals(10, \count($result));
    }

    public function testCreateAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/team/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_admin_edit_form]')->form();
        $client->submit($form, [
            'timesheet_admin_edit_form' => [
                'description' => 'Testing is fun!',
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/team/timesheet/'));
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

    public function testCreateForMultipleUsersAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->request($client, '/team/timesheet/create_mu');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_multi_user_edit_form]')->form();
        $client->submit($form, [
            'timesheet_multi_user_edit_form' => [
                'description' => 'Testing is more fun!',
                'project' => 1,
                'activity' => 1,
                'teams' => '1',
                'tags' => 'test,1234,foo-bar',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/team/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        $this->assertCount(2, $timesheets);
        foreach ($timesheets as $timesheet) {
            $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
            $this->assertNull($timesheet->getEnd());
            $this->assertEquals('Testing is more fun!', $timesheet->getDescription());
            $this->assertEquals(0, $timesheet->getRate());
            $this->assertNull($timesheet->getHourlyRate());
            $this->assertNull($timesheet->getFixedRate());
            $this->assertEquals(['test', '1234', 'foo-bar'], $timesheet->getTagsAsArray());
        }
    }

    public function testCreateForMultipleUsersActionWithoutUserOrTeam(): void
    {
        $begin = new \DateTime();
        $end = new \DateTime('+1 hour');
        $data = [
            'timesheet_multi_user_edit_form' => [
                'description' => 'Testing is more fun!',
                'project' => 1,
                'activity' => 1,
                // make sure the default validation for timesheets is applied as well
                'begin_date' => $this->formatDate($begin),
                'begin_time' => $this->formatTime($begin),
                'end_time' => $this->formatTime($end),
            ]
        ];

        $this->assertFormHasValidationError(
            User::ROLE_ADMIN,
            '/team/timesheet/create_mu',
            'form[name=timesheet_multi_user_edit_form]',
            $data,
            ['#timesheet_multi_user_edit_form_users', '#timesheet_multi_user_edit_form_teams']
        );
    }

    public function testEditAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->setSystemConfiguration('timesheet.rules.long_running_duration', '1440');

        $user = $this->getUserByRole(User::ROLE_USER);
        $teamlead = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
        $fixture->setFixedStartDate(new \DateTime('-2 hours'));
        $timesheets = $this->importFixture($fixture);
        $id = $timesheets[0]->getId();

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->request($client, '/team/timesheet/' . $id . '/edit');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $this->assertStringContainsString(
            'href="https://www.kimai.org/documentation/timesheet.html"',
            $response->getContent(),
            'Could not find link to documentation'
        );

        $form = $client->getCrawler()->filter('form[name=timesheet_admin_edit_form]')->form();
        $client->submit($form, [
            'timesheet_admin_edit_form' => [
                'description' => 'foo-bar',
                'tags' => 'foo,bar, testing, hello world,,',
                'user' => $teamlead->getId()
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/team/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSaveSuccess($client);

        $em = $this->getEntityManager();
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find($id);
        $this->assertEquals('foo-bar', $timesheet->getDescription());
        $this->assertEquals($teamlead->getId(), $timesheet->getUser()->getId());
    }

    public function testMultiDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $user = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/team/timesheet/');

        $form = $client->getCrawler()->filter('form[name=multi_update_table]')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/team/timesheet/multi-delete'));

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
        $this->assertIsRedirect($client, $this->createUrl('/team/timesheet/'));
        $client->followRedirect();

        $em->clear();
        self::assertEquals(0, $em->getRepository(Timesheet::class)->count([]));
    }

    public function testMultiUpdate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $user = $this->getUserByRole(User::ROLE_TEAMLEAD);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
        $this->importFixture($fixture);

        $fixture = new TagFixtures();
        $fixture->importAmount(TagRepository::MAX_AMOUNT_SELECT);
        $this->importFixture($fixture);

        $this->assertAccessIsGranted($client, '/team/timesheet/');

        $form = $client->getCrawler()->filter('form[name=multi_update_table]')->form();
        $node = $form->getFormNode();
        $node->setAttribute('action', $this->createUrl('/team/timesheet/multi-update'));

        $em = $this->getEntityManager();
        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(10, $timesheets);
        $ids = [];
        foreach ($timesheets as $timesheet) {
            self::assertFalse($timesheet->isExported());
            self::assertEquals($user->getId(), $timesheet->getUser()->getId());
            $ids[] = $timesheet->getId();
        }
        // FIXME
        $client->submit($form, [
            'multi_update_table' => [
                'entities' => implode(',', $ids)
            ]
        ]);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $newUser = $this->getUserByRole(User::ROLE_USER);
        $form = $client->getCrawler()->filter('form[name=timesheet_multi_update]')->form();
        $client->submit($form, [
            'timesheet_multi_update' => [
                'user' => $newUser->getId(),
                'exported' => true,
                'replaceTags' => true,
                'tags' => 'test, foo-bar, tralalala',
                'hourlyRate' => 13.78,
            ]
        ]);

        $em->clear();

        /** @var Timesheet[] $timesheets */
        $timesheets = $em->getRepository(Timesheet::class)->findAll();
        self::assertCount(10, $timesheets);
        foreach ($timesheets as $timesheet) {
            self::assertCount(3, $timesheet->getTags());
            self::assertEquals($newUser->getId(), $timesheet->getUser()->getId());
            self::assertTrue($timesheet->isExported());
            self::assertEquals(Util::calculateRate(13.78, $timesheet->getDuration()), $timesheet->getRate());
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

        $this->request($client, '/team/timesheet/' . $newId . '/duplicate');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_admin_edit_form]')->form();
        $client->submit($form, $form->getPhpValues());

        $this->assertIsRedirect($client, $this->createUrl('/team/timesheet/'));
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
