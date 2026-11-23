<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Export\ExportRendererInterface;
use App\Repository\Query\TimesheetQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractRendererTestCase extends KernelTestCase
{
    protected function render(ExportRendererInterface $renderer, bool $exportDecimal = false): Response
    {
        $customer = new Customer('Customer Name');
        $customer->setNumber('A-0123456789');
        $customer->setVatId('DE-9876543210');
        $customer->setMetaField((new CustomerMeta())->setName('customer-foo')->setValue('customer-bar')->setIsVisible(true));

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setOrderNumber('ORDER-123');
        $project->setMetaField((new ProjectMeta())->setName('project-bar')->setValue('project-bar')->setIsVisible(true));
        $project->setMetaField((new ProjectMeta())->setName('project-foo2')->setValue('project-foo2')->setIsVisible(true));

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('activity-foo')->setValue('activity-bar')->setIsVisible(true));

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername', 'getUserIdentifier', 'getAlias'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getAlias')->willReturn('Foo Bar');
        $user1->method('getUserIdentifier')->willReturn('foo-bar');
        $user1->method('getUsername')->willReturn('foo-bar');

        $user2 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);
        $user2->method('getAlias')->willReturn('Hello World');
        $user2->method('getUserIdentifier')->willReturn('hello-world');
        $user2->method('getUsername')->willReturn('hello-world');

        $timesheet = new Timesheet();
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user1);
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        $timesheet->setBegin(new \DateTime());
        $timesheet->setEnd(new \DateTime());

        $timesheet2 = new Timesheet();
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user2);
        $timesheet2->setActivity($activity);
        $timesheet2->setProject($project);
        $timesheet2->setBegin(new \DateTime());
        $timesheet2->setEnd(new \DateTime());

        $timesheet3 = new Timesheet();
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user1);
        $timesheet3->setActivity($activity);
        $timesheet3->setProject($project);
        $timesheet3->setBegin(new \DateTime());
        $timesheet3->setEnd(new \DateTime());

        $timesheet4 = new Timesheet();
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user2);
        $timesheet4->setDescription('== jhg ljhg '); // make sure that spreadsheets don't render it as formula
        $timesheet4->setActivity($activity);
        $timesheet4->setProject($project);
        $timesheet4->setBegin(new \DateTime());
        $timesheet4->setEnd(new \DateTime());
        $timesheet4->addTag((new Tag())->setName('foo'));

        $userKevin = new User();
        $userKevin->setAlias('Kevin');
        $userKevin->setUserIdentifier('kevin');

        $timesheet5 = new Timesheet();
        $timesheet5->setDuration(400);
        $timesheet5->setFixedRate(84);
        $timesheet5->setUser($userKevin);
        $timesheet5->setActivity($activity);
        $timesheet5->setProject($project);
        $timesheet5->setBegin(new \DateTime('2019-06-16 12:00:00'));
        $timesheet5->setEnd(new \DateTime('2019-06-16 12:06:40'));
        $timesheet5->addTag((new Tag())->setName('foo'));
        $timesheet5->addTag((new Tag())->setName('bar'));
        $timesheet5->setMetaField((new TimesheetMeta())->setName('foo')->setValue('meta-bar')->setIsVisible(true));
        $timesheet5->setMetaField((new TimesheetMeta())->setName('foo2')->setValue('meta-bar2')->setIsVisible(true));

        $userNivek = new User();
        $userNivek->setAlias('niveK');
        $userNivek->setUserIdentifier('nivek');

        $timesheet6 = new Timesheet();
        $timesheet6->setDuration(400);
        $timesheet6->setFixedRate(-100.92);
        $timesheet6->setUser($userNivek);
        $timesheet6->setActivity($activity);
        $timesheet6->setProject($project);
        $timesheet6->setBegin(new \DateTime('2019-06-16 12:00:00'));
        $timesheet6->setEnd(new \DateTime('2019-06-16 12:06:40'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet6];

        $currentUser = new User();
        $currentUser->setPreferenceValue('export_decimal', $exportDecimal);
        $currentUser->setUserIdentifier('foo-bar');

        $query = new TimesheetQuery();
        $query->setActivities([$activity]);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProjects([$project]);
        $query->setCurrentUser($currentUser);

        return $renderer->render($entries, $query);
    }
}
