<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Timesheet;

use App\Configuration\LocaleService;
use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Event\ActivityMetaDisplayEvent;
use App\Event\CustomerMetaDisplayEvent;
use App\Event\ProjectMetaDisplayEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\TimesheetExportInterface;
use App\Repository\Query\TimesheetQuery;
use App\Twig\LocaleFormatExtensions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractRendererTest extends KernelTestCase
{
    /**
     * @param string $classname
     * @return TimesheetExportInterface
     */
    protected function getAbstractRenderer(string $classname)
    {
        $languages = [
            'en' => [
                'date' => 'Y.m.d',
                'duration' => '%h:%m',
                'time' => 'H:i',
            ]
        ];

        $user = new User();
        $user->setUserIdentifier('ssdf');

        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn($user);
        $security->expects($this->any())->method('isGranted')->willReturn(true);

        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $dateExtension = new LocaleFormatExtensions(new LocaleService($languages), $security);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new MetaFieldColumnSubscriber());

        return new $classname($translator, $dateExtension, $dispatcher, $security);
    }

    /**
     * @param TimesheetExportInterface $renderer
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function render(TimesheetExportInterface $renderer)
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

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername', 'getUserIdentifier'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');
        $user1->method('getUserIdentifier')->willReturn('foo-bar');

        $user2 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);
        $user2->method('getUsername')->willReturn('hello-world');
        $user2->method('getUserIdentifier')->willReturn('hello-world');

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->addTag((new Tag())->setName('foo'))
        ;

        $user = new User();
        $user->setUserIdentifier('kevin');

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setDuration(400)
            ->setFixedRate(84)
            ->setUser($user)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2019-06-16 12:00:00'))
            ->setEnd(new \DateTime('2019-06-16 12:06:40'))
            ->addTag((new Tag())->setName('foo'))
            ->addTag((new Tag())->setName('bar'))
            ->setMetaField((new TimesheetMeta())->setName('foo')->setValue('meta-bar')->setIsVisible(true))
            ->setMetaField((new TimesheetMeta())->setName('foo2')->setValue('meta-bar2')->setIsVisible(true))
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new TimesheetQuery();
        $query->setActivities([$activity]);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProjects([$project]);

        return $renderer->render($entries, $query);
    }
}

class MetaFieldColumnSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDisplayEvent::class => ['loadTimesheetField', 200],
            CustomerMetaDisplayEvent::class => ['loadCustomerField', 200],
            ProjectMetaDisplayEvent::class => ['loadProjectField', 200],
            ActivityMetaDisplayEvent::class => ['loadActivityField', 200],
        ];
    }

    public function loadTimesheetField(TimesheetMetaDisplayEvent $event)
    {
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo'));
        $event->addField($this->prepareEntity(new TimesheetMeta(), 'foo2'));
    }

    public function loadCustomerField(CustomerMetaDisplayEvent $event)
    {
        $event->addField($this->prepareEntity(new CustomerMeta(), 'customer-foo'));
    }

    public function loadProjectField(ProjectMetaDisplayEvent $event)
    {
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo'));
        $event->addField($this->prepareEntity(new ProjectMeta(), 'project-foo2')->setIsVisible(false));
    }

    public function loadActivityField(ActivityMetaDisplayEvent $event)
    {
        $event->addField($this->prepareEntity(new ActivityMeta(), 'activity-foo'));
    }

    private function prepareEntity(MetaTableTypeInterface $meta, string $name)
    {
        return $meta
            ->setLabel('Working place')
            ->setName($name)
            ->setType(TextType::class)
            ->setIsVisible(true);
    }
}
