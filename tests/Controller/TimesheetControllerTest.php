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
use App\Form\Type\DateRangeType;
use App\Tests\DataFixtures\TimesheetFixtures;
use App\Tests\Mocks\TimesheetTestMetaFieldSubscriberMock;

/**
 * @group integration
 */
class TimesheetControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/timesheet/');
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        // there are no records by default in the test database
        $this->assertHasNoEntriesWithFilter($client);
        $this->assertPageActions($client, [
            'search search-toggle visible-xs-inline' => '#',
            'toolbar-action exporter-csv' => $this->createUrl('/timesheet/export/csv'),
            'toolbar-action exporter-print' => $this->createUrl('/timesheet/export/print'),
            'toolbar-action exporter-pdf' => $this->createUrl('/timesheet/export/pdf'),
            'toolbar-action exporter-xlsx' => $this->createUrl('/timesheet/export/xlsx'),
            'visibility' => '#',
            'create modal-ajax-form' => $this->createUrl('/timesheet/create'),
            'help' => 'https://www.kimai.org/documentation/timesheet.html'
        ]);
    }

    public function testIndexActionWithQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $em = $this->getEntityManager();
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setAmountRunning(2);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate($start);
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = ($start)->format('Y-m-d') . DateRangeType::DATE_SPACER . (new \DateTime('last day of this month'))->format('Y-m-d');

        $form = $client->getCrawler()->filter('form.header-search')->form();
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
        $node = $client->getCrawler()->filter('section.content div#datatable_timesheet table.table-striped tbody tr.recording');
        self::assertEquals(2, $node->count());
    }

    public function testIndexActionWithSearchTermQuery()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $start = new \DateTime('first day of this month');

        $em = $this->getEntityManager();
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

        $dateRange = ($start)->format('Y-m-d') . DateRangeType::DATE_SPACER . (new \DateTime('last day of this month'))->format('Y-m-d');

        $form = $client->getCrawler()->filter('form.header-search')->form();
        $client->submit($form, [
            'searchTerm' => 'location:homeoffice foobar',
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);
        $this->assertDataTableRowCount($client, 'datatable_timesheet', 5);
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser();

        $em = $this->getEntityManager();
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = (new \DateTime('-10 days'))->format('Y-m-d') . DateRangeType::DATE_SPACER . (new \DateTime())->format('Y-m-d');

        $form = $client->getCrawler()->filter('form.header-search')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/timesheet/export/print'));
        $client->submit($form, [
            'state' => 1,
            'pageSize' => 25,
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

    public function testCreateAction()
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
                // there was a bug with end begin required, so we manually set this field to be empty
                'end' => null,
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
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals('Testing is fun!', $timesheet->getDescription());
        $this->assertEquals(0, $timesheet->getRate());
        $this->assertNull($timesheet->getHourlyRate());
        $this->assertNull($timesheet->getFixedRate());
    }

    public function testCreateActionShowsMetaFields()
    {
        $client = $this->getClientForAuthenticatedUser();
        static::$kernel->getContainer()->get('event_dispatcher')->addSubscriber(new TimesheetTestMetaFieldSubscriberMock());
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $this->assertTrue($form->has('timesheet_edit_form[metaFields][0][value]'));
        $this->assertFalse($form->has('timesheet_edit_form[metaFields][1][value]'));
    }

    public function testCreateActionDoesNotShowRateFieldsForUser()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $this->assertFalse($form->has('hourlyRate'));
        $this->assertFalse($form->has('fixedRate'));
    }

    public function testCreateActionWithFromAndToValues()
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
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(50, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T20:00:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getBegin()->format(\DateTime::ATOM));

        $expected = new \DateTime('2018-08-02T20:30:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getEnd()->format(\DateTime::ATOM));
    }

    public function testCreateActionWithBeginAndEndAndTagValues()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
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
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(800, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T10:00:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getBegin()->format(\DateTime::ATOM));

        $expected = new \DateTime('2018-08-02T18:00:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getEnd()->format(\DateTime::ATOM));

        $this->assertEquals(['one', 'two', 'three'], $timesheet->getTagsAsArray());
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser();

        $em = $this->getEntityManager();
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($this->getUserByRole(User::ROLE_USER));
        $fixture->setStartDate('2017-05-01');
        $this->importFixture($fixture);

        $this->request($client, '/timesheet/1/edit');

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
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertEquals('foo-bar', $timesheet->getDescription());
    }

    public function testMultiDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $this->getEntityManager();
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
                'action' => $this->createUrl('/timesheet/multi-delete'),
                'entities' => implode(',', $ids)
            ]
        ]);
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();

        $em->clear();
        self::assertEquals(0, $em->getRepository(Timesheet::class)->count([]));
    }

    public function testMultiUpdate()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $em = $this->getEntityManager();
        $user = $this->getUserByRole(User::ROLE_SUPER_ADMIN);
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($user);
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

        $client->submit($form, [
            'multi_update_table' => [
                'action' => $this->createUrl('/timesheet/multi-update'),
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
}
