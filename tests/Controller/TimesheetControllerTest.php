<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Type\DateRangeType;
use App\Tests\DataFixtures\TimesheetFixtures;

/**
 * @coversDefaultClass \App\Controller\TimesheetController
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
        $this->assertHasDataTable($client);

        $result = $client->getCrawler()->filter('div.breadcrumb div.box-tools div.btn-group a.btn');
        $this->assertEquals(5, count($result));

        foreach ($result as $item) {
            $this->assertEquals('btn btn-default', $item->getAttribute('class'));
            $this->assertEquals('i', $item->firstChild->tagName);
        }
    }

    public function testIndexActionWithQuery()
    {
        $client = $this->getClientForAuthenticatedUser();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($em, $fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = (new \DateTime('-10 days'))->format('Y-m-d') . DateRangeType::DATE_SPACER . (new \DateTime())->format('Y-m-d');

        $form = $client->getCrawler()->filter('form.navbar-form')->form();
        $client->submit($form, [
            'state' => 1,
            'pageSize' => 25,
            'daterange' => $dateRange,
            'customer' => null,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasDataTable($client);

        // TODO more assertions
    }

    public function testExportAction()
    {
        $client = $this->getClientForAuthenticatedUser();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(5);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate(new \DateTime('-10 days'));
        $this->importFixture($em, $fixture);

        $this->request($client, '/timesheet/');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $dateRange = (new \DateTime('-10 days'))->format('Y-m-d') . DateRangeType::DATE_SPACER . (new \DateTime())->format('Y-m-d');

        $form = $client->getCrawler()->filter('form.navbar-form')->form();
        $form->getFormNode()->setAttribute('action', $this->createUrl('/timesheet/export'));
        $client->submit($form, [
            'state' => 1,
            'pageSize' => 25,
            'daterange' => $dateRange,
            'customer' => null,
        ]);

        $this->assertTrue($client->getResponse()->isSuccessful());

        $node = $client->getCrawler()->filter('body');
        $this->assertEquals('invoice_print', $node->getNode(0)->getAttribute('class'));

        $result = $node->filter('section.invoice table.table tbody tr');
        $this->assertEquals(5, count($result));
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
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals('Testing is fun!', $timesheet->getDescription());
        $this->assertEquals(0, $timesheet->getRate());
        $this->assertNull($timesheet->getHourlyRate());
        $this->assertNull($timesheet->getFixedRate());
    }

    public function testDeleteAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate('2017-05-01');
        $this->importFixture($em, $fixture);

        $this->request($client, '/timesheet/1/edit');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->request($client, '/timesheet/1/delete');
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/page/1'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/timesheet/1/edit');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testStartAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setAmount(1);
        $this->importFixture($em, $fixture);

        $this->request($client, '/timesheet/start/1');

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(2);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertNull($timesheet->getEnd());
        $this->assertEquals(1, $timesheet->getActivity()->getId());
        $this->assertEquals(1, $timesheet->getProject()->getId());
    }

    public function testStopActionDoesNotShowRateFieldsForUser()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $this->assertFalse($form->has('hourlyRate'));
        $this->assertFalse($form->has('fixedRate'));
    }

    public function testStopAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/timesheet/create');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $client->getCrawler()->filter('form[name=timesheet_edit_form]')->form();
        $client->submit($form, [
            'timesheet_edit_form' => [
                'description' => 'Testing is fun!',
                'fixedRate' => 100,
                'project' => 1,
                'activity' => 1,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $this->request($client, '/timesheet/1/stop');
        $this->assertIsRedirect($client, $this->createUrl('/timesheet/'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(100, $timesheet->getRate());
        $this->assertEquals(100, $timesheet->getFixedRate());
        $this->assertNull($timesheet->getHourlyRate());
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

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
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

    public function testCreateActionWithBeginAndEndValues()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/timesheet/create?begin=2018-08-02&end=2018-08-02');
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

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /** @var Timesheet $timesheet */
        $timesheet = $em->getRepository(Timesheet::class)->find(1);
        $this->assertInstanceOf(\DateTime::class, $timesheet->getBegin());
        $this->assertInstanceOf(\DateTime::class, $timesheet->getEnd());
        $this->assertEquals(800, $timesheet->getRate());

        $expected = new \DateTime('2018-08-02T10:00:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getBegin()->format(\DateTime::ATOM));

        $expected = new \DateTime('2018-08-02T18:00:00');
        $this->assertEquals($expected->format(\DateTime::ATOM), $timesheet->getEnd()->format(\DateTime::ATOM));
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $fixture = new TimesheetFixtures();
        $fixture->setAmount(10);
        $fixture->setUser($this->getUserByRole($em, User::ROLE_USER));
        $fixture->setStartDate('2017-05-01');
        $this->importFixture($em, $fixture);

        $this->request($client, '/timesheet/1/edit');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $docuUrl = $this->createUrl('/help/timesheet');
        $this->assertContains(
            '<a href="' . $docuUrl . '"><i class="far fa-question-circle"></i></a>',
            $response->getContent(),
            'Could not find link to documentation'
        );

        // TODO more assertions
    }
}
