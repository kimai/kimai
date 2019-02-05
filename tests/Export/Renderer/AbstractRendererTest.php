<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Export\RendererInterface;
use App\Repository\Query\TimesheetQuery;
use App\Twig\DateExtensions;
use App\Twig\Extensions;
use App\Utils\LocaleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractRendererTest extends KernelTestCase
{
    /**
     * @param string $classname
     * @return RendererInterface
     */
    protected function getAbstractRenderer(string $classname)
    {
        $requestStack = new RequestStack();
        $languages = [
            'en' => [
                'date' => 'Y.m.d',
                'duration' => '%h:%m h'
            ]
        ];

        $request = new Request();
        $request->setLocale('en');
        $requestStack->push($request);

        $localeSettings = new LocaleSettings($requestStack, $languages);

        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $dateExtension = new DateExtensions($localeSettings);
        $extensions = new Extensions($requestStack, $localeSettings);

        return new $classname($translator, $dateExtension, $extensions);
    }

    /**
     * @param RendererInterface $renderer
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function render(RendererInterface $renderer)
    {
        $customer = new Customer();
        $customer->setName('Customer Name');

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername'];
        $user1 = $this->getMockBuilder(User::class)->setMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');

        $user2 = $this->getMockBuilder(User::class)->setMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);
        $user2->method('getUsername')->willReturn('hello-world');

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
        ;

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setDuration(400)
            ->setFixedRate(84)
            ->setUser((new User())->setUsername('kevin'))
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new TimesheetQuery();
        $query->setActivity($activity);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProject($project);

        return $renderer->render($entries, $query);
    }
}
