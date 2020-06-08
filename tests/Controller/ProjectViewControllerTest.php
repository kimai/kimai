<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\ProjectRepository;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @group integration
 * @group time-sensitive
 */
class ProjectViewControllerTest extends ControllerBaseTest
{
    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();

        ClockMock::register(ProjectRepository::class);
        ClockMock::withClockMock((new \Datetime('2020-06-02'))->getTimestamp());

        $this->prepareFixtures();

        $this->assertAccessIsGranted($client, '/project_view', 'GET');

        $firstRow = $client->getCrawler()->filterXpath("//table[@id='dt_project_view']/tbody/tr[1]");
        $this->assertEquals('Project #1', $firstRow->filterXpath('//td[1]')->text());
        $this->assertEquals('00:13 h', $firstRow->filterXpath('//td[2]')->text());
        $this->assertEquals('00:21 h', $firstRow->filterXpath('//td[3]')->text());
        $this->assertEquals('00:26 h', $firstRow->filterXpath('//td[4]')->text());
        $this->assertEquals('00:05 h', $firstRow->filterXpath('//td[5]')->text());
        $this->assertEquals('2020/06/02', $firstRow->filterXpath('//td[6]')->text());
    }

    private function prepareFixtures() {
        $em = $this->getEntityManager();

        $customer = (new Customer())->setName('Customer #1')->setVisible(true)->setCountry('BR')->setTimezone('America/Sao_Paulo');
        $em->persist($customer);
        $em->flush();

        $project = (new Project())
            ->setName('Project #1')
            ->setTimeBudget(300)
            ->setEnd(\DateTime::createFromFormat('U', time()))
            ->setVisible(true)
            ->setCustomer($customer)
        ;
        $em->persist($project);
        $em->flush();

        $activity1 = (new Activity())->setName('Activity #1')->setProject($project);
        $em->persist($activity1);
        $activity2 = (new Activity())->setName('Activity #2')->setProject($project);
        $em->persist($activity2);
        $em->flush();

        $user = $this->getUserByRole();
        $timesheet = (new Timesheet())
            ->setBegin(\DateTime::createFromFormat('U', time())->modify('-7 days'))
            ->setEnd(\DateTime::createFromFormat('U', time())->modify('-7 days')->modify('+5 minutes'))
            ->setProject($project)
            ->setActivity($activity1)
            ->setUser($user)
        ;
        $em->persist($timesheet);
        $timesheet = (new Timesheet())
            ->setBegin(\DateTime::createFromFormat('U', time())->modify('-1 day'))
            ->setEnd(\DateTime::createFromFormat('U', time())->modify('-1 day')->modify('+8 minutes'))
            ->setProject($project)
            ->setActivity($activity1)
            ->setUser($user)
        ;
        $em->persist($timesheet);
        $timesheet = (new Timesheet())
            ->setBegin(\DateTime::createFromFormat('U', time()))
            ->setEnd(\DateTime::createFromFormat('U', time())->modify('+13 minutes'))
            ->setProject($project)
            ->setActivity($activity2)
            ->setUser($user)
        ;
        $em->persist($timesheet);
        $em->flush();
    }
}
