<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Configuration\LocaleService;
use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Timesheet;
use App\Entity\TimesheetMeta;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\DefaultInvoiceFormatter;
use App\Invoice\InvoiceFormatter;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\NumberGeneratorInterface;
use App\Invoice\Renderer\AbstractRenderer;
use App\Model\InvoiceDocument;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use Doctrine\Common\Collections\ArrayCollection;

trait RendererTestTrait
{
    protected function getInvoiceTemplatePath(): string
    {
        return __DIR__ . '/../../../templates/invoice/renderer/';
    }

    protected function getInvoiceDocument(string $filename, bool $testOnly = false): InvoiceDocument
    {
        if (!$testOnly) {
            return new InvoiceDocument(
                new \SplFileInfo($this->getInvoiceTemplatePath() . $filename)
            );
        }

        return new InvoiceDocument(
            new \SplFileInfo(__DIR__ . '/../templates/' . $filename)
        );
    }

    protected function getAbstractRenderer(string $classname): AbstractRenderer
    {
        return new $classname();
    }

    protected function getFormatter(): InvoiceFormatter
    {
        $languages = [
            'en' => [
                'date' => 'yy.MM.dd',
                'time' => 'H:i',
                'rtl' => false,
                'translation' => true,
            ]
        ];

        $formattings = new LocaleService($languages);

        return new DefaultInvoiceFormatter($formattings, 'en');
    }

    protected function getInvoiceModel(): InvoiceModel
    {
        $user = new User();
        $user->setUserIdentifier('one-user');
        $user->setTitle('user title');
        $user->setAlias('genious alias');
        $user->setEmail('fantastic@four');
        $user->addPreference(new UserPreference('kitty', 'kat'));
        $user->addPreference(new UserPreference('hello', 'world'));

        $customer = new Customer('customer,with/special#name');
        $customer->setAddress('Foo' . PHP_EOL . 'Street' . PHP_EOL . '1111 City');
        $customer->setCurrency('EUR');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a very *long* test invoice / template title with [ßpecial] chäracter');
        $template->setVat(19);
        $template->setLanguage('en');

        $pMeta = new ProjectMeta();
        $pMeta->setName('foo-project')->setValue('bar-project')->setIsVisible(true);
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(0);
        $project->method('getName')->willReturn('project name');
        $project->method('getCustomer')->willReturn($customer);
        $project->method('getMetaFields')->willReturn(new ArrayCollection([$pMeta]));
        $project->method('getVisibleMetaFields')->willReturn([$pMeta]);

        $aMeta = new ActivityMeta();
        $aMeta->setName('foo-activity');
        $aMeta->setValue('bar-activity');
        $aMeta->setIsVisible(true);
        $activity = $this->createMock(Activity::class);
        $activity->method('getId')->willReturn(0);
        $activity->method('getName')->willReturn('activity description');
        $activity->method('getProject')->willReturn($project);
        $activity->method('getMetaFields')->willReturn(new ArrayCollection([$aMeta]));
        $activity->method('getVisibleMetaFields')->willReturn([$aMeta]);

        $pMeta2 = new ProjectMeta();
        $pMeta2->setName('foo-project')->setValue('bar-project2')->setIsVisible(true);
        $project2 = $this->createMock(Project::class);
        $project2->method('getId')->willReturn(1);
        $project2->method('getName')->willReturn('project 2 name');
        $project2->method('getCustomer')->willReturn($customer);
        $project2->method('getMetaFields')->willReturn(new ArrayCollection([$pMeta2]));
        $project2->method('getVisibleMetaFields')->willReturn([$pMeta2]);

        $aMeta2 = new ActivityMeta();
        $aMeta2->setName('foo-activity');
        $aMeta2->setValue('bar-activity2');
        $aMeta2->setIsVisible(true);
        $activity2 = $this->createMock(Activity::class);
        $activity2->method('getId')->willReturn(1);
        $activity2->method('getName')->willReturn('activity 1 description');
        $activity2->method('getProject')->willReturn($project2);
        $activity2->method('getMetaFields')->willReturn(new ArrayCollection([$aMeta2]));
        $activity2->method('getVisibleMetaFields')->willReturn([$aMeta2]);

        $pref1 = new UserPreference('foo', 'bar');
        $pref2 = new UserPreference('mad', 123.45);
        $userMethods = ['getId', 'getPreferenceValue', 'getVisiblePreferences', 'getUsername', 'getUserIdentifier'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');
        $user1->method('getUserIdentifier')->willReturn('foo-bar');
        $user1->method('getVisiblePreferences')->willReturn([$pref1, $pref2]);

        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);
        $user2->method('getUsername')->willReturn('hello-world');
        $user2->method('getUserIdentifier')->willReturn('hello-world');
        $user2->method('getVisiblePreferences')->willReturn([$pref1, $pref2]);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2020-12-13 14:00:00'))
            ->setEnd(new \DateTime('2020-12-13 15:00:00'))
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet')->setIsVisible(true));

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2020-08-13 14:00:00'))
            ->setEnd(new \DateTime('2020-08-13 14:06:40'))
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet'))
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet2')->setValue('bar-timesheet2')->setIsVisible(true))
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity2)
            ->setDescription('== jhg ljhg ') // make sure that spreadsheets don't render it as formula
            ->setProject($project2)
            ->setBegin(new \DateTime('2020-08-12 18:00:00'))
            ->setEnd(new \DateTime('2020-08-12 18:30:00'))
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet1')->setIsVisible(true))
        ;

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2020-12-13 14:00:00'))
            ->setEnd(new \DateTime('2020-12-13 14:06:40'))
            ->setDescription(
                "foo\n" .
                "foo\r\n" .
                'foo' . PHP_EOL .
                "bar\n" .
                "bar\r\n" .
                'Hello'
            )
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet3')->setValue('bluuuub')->setIsVisible(true))
        ;

        $userKevin = new User();
        $userKevin->setUserIdentifier('kevin');
        $userKevin->addPreference($pref1);
        $userKevin->addPreference($pref2);

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setDuration(400)
            ->setFixedRate(84)
            ->setUser($userKevin)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2021-03-12 12:13:00'))
            ->setEnd(new \DateTime('2021-03-12 12:17:40'))
            ->setDescription(
                "foo\n" .
                "foo\r\n" .
                'foo' . PHP_EOL .
                "bar\n" .
                "bar\r\n" .
                'Hello'
            )
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new InvoiceQuery();
        $query->setActivities([$activity, $activity2]);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProjects([$project, $project2]);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel($this->getFormatter(), $customer, $template, $query);
        $model->addEntries($entries);
        $model->setUser($user);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = $this->getNumberGeneratorSut();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }

    private function getNumberGeneratorSut(): NumberGeneratorInterface
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('hasInvoice')
            ->willReturn(false);

        return new DateNumberGenerator($repository);
    }

    protected function getInvoiceModelOneEntry(): InvoiceModel
    {
        $user = new User();
        $user->setUserIdentifier('one-user');
        $user->setTitle('user title');
        $user->setAlias('genious alias');
        $user->setEmail('fantastic@four');
        $user->addPreference(new UserPreference('kitty', 'kat'));
        $user->addPreference(new UserPreference('hello', 'world'));

        $customer = new Customer('customer,with/special#name');
        $customer->setCurrency('USD');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a test invoice template title');
        $template->setVat(19);
        $template->setLanguage('it');

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setMetaField((new ProjectMeta())->setName('foo-project')->setValue('bar-project')->setIsVisible(true));

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('foo-activity')->setValue('bar-activity')->setIsVisible(true));

        $pref1 = new UserPreference('foo', 'bar');
        $pref2 = new UserPreference('mad', 123.45);
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');
        $user1->method('getUserIdentifier')->willReturn('foo-bar');
        $user1->method('getVisiblePreferences')->willReturn([$pref1, $pref2]);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2020-08-12 18:00:00'))
            ->setEnd(new \DateTime('2021-03-12 18:30:00'))
        ;

        $entries = [$timesheet];

        $query = new InvoiceQuery();
        $query->addActivity($activity);
        $query->addProject($project);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel($this->getFormatter(), $customer, $template, $query);
        $model->addEntries($entries);
        $model->setUser($user);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = $this->getNumberGeneratorSut();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }
}
