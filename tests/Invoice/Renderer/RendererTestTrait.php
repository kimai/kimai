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

    /**
     * @param class-string $classname
     */
    protected function getAbstractRenderer(string $classname): AbstractRenderer
    {
        $t = new $classname();
        if (!$t instanceof AbstractRenderer) {
            throw new \InvalidArgumentException('Not an instance of AbstractRenderer: ' . \get_class($t));
        }

        return $t;
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
        $activityId = new \ReflectionProperty(Activity::class, 'id');
        $projectId = new \ReflectionProperty(Project::class, 'id');
        $userId = new \ReflectionProperty(User::class, 'id');

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
        $customer->setCountry('AT');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a very *long* test invoice / template title with [ßpecial] chäracter');
        $template->setVat(19);
        $template->setLanguage('en');
        $template->setCustomer($customer);

        $pMeta = new ProjectMeta();
        $pMeta->setName('foo-project')->setValue('bar-project')->setIsVisible(true);
        $project = new Project();
        $projectId->setValue($project, 0);
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setMetaField($pMeta);

        $aMeta = new ActivityMeta();
        $aMeta->setName('foo-activity');
        $aMeta->setValue('bar-activity');
        $aMeta->setIsVisible(true);
        $activity = new Activity();
        $activityId->setValue($activity, 0);
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField($aMeta);

        $pMeta2 = new ProjectMeta();
        $pMeta2->setName('foo-project')->setValue('bar-project2')->setIsVisible(true);
        $project2 = new Project();
        $projectId->setValue($project2, 1);
        $project2->setName('project 2 name');
        $project2->setCustomer($customer);
        $project2->setMetaField($pMeta2);

        $aMeta2 = new ActivityMeta();
        $aMeta2->setName('foo-activity');
        $aMeta2->setValue('bar-activity2');
        $aMeta2->setIsVisible(true);
        $activity2 = new Activity();
        $activityId->setValue($activity2, 1);
        $activity2->setName('activity 1 description');
        $activity2->setProject($project2);
        $activity2->setMetaField($aMeta2);

        $pref1 = new UserPreference('foo', 'bar');
        $pref2 = new UserPreference('mad', 123.45);
        $user1 = new User();
        $user1->setUserIdentifier('foo-bar');
        $userId->setValue($user1, 1);
        //$user1->method('getPreferenceValue')->willReturn('50');
        $user1->addPreference($pref1);
        $user1->addPreference($pref2);

        $user2 = new User();
        $userId->setValue($user2, 2);
        $user2->setUserIdentifier('hello-world');
        $user2->addPreference($pref1);
        $user2->addPreference($pref2);

        $timesheet = new Timesheet();
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user1);
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        $timesheet->setBegin(new \DateTime('2020-12-13 14:00:00'));
        $timesheet->setEnd(new \DateTime('2020-12-13 15:00:00'));
        $timesheet->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet')->setIsVisible(true));

        $timesheet2 = new Timesheet();
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user2);
        $timesheet2->setActivity($activity);
        $timesheet2->setProject($project);
        $timesheet2->setBegin(new \DateTime('2020-08-13 14:00:00'));
        $timesheet2->setEnd(new \DateTime('2020-08-13 14:06:40'));
        $timesheet2->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet'));
        $timesheet2->setMetaField((new TimesheetMeta())->setName('foo-timesheet2')->setValue('bar-timesheet2')->setIsVisible(true));

        $timesheet3 = new Timesheet();
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user1);
        $timesheet3->setActivity($activity2);
        $timesheet3->setDescription('== jhg ljhg '); // make sure that spreadsheets don't render it as formula
        $timesheet3->setProject($project2);
        $timesheet3->setBegin(new \DateTime('2020-08-12 18:00:00'));
        $timesheet3->setEnd(new \DateTime('2020-08-12 18:30:00'));
        $timesheet3->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet1')->setIsVisible(true));

        $timesheet4 = new Timesheet();
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user2);
        $timesheet4->setActivity($activity);
        $timesheet4->setProject($project);
        $timesheet4->setBegin(new \DateTime('2020-12-13 14:00:00'));
        $timesheet4->setEnd(new \DateTime('2020-12-13 14:06:40'));
        $timesheet4->setDescription(
            "foo\n" .
            "foo\r\n" .
            'foo' . PHP_EOL .
            "bar\n" .
            "bar\r\n" .
            'Hello'
        );
        $timesheet4->setMetaField((new TimesheetMeta())->setName('foo-timesheet3')->setValue('bluuuub')->setIsVisible(true));

        $userKevin = new User();
        $userKevin->setUserIdentifier('kevin');
        $userKevin->addPreference($pref1);
        $userKevin->addPreference($pref2);

        $timesheet5 = new Timesheet();
        $timesheet5->setDuration(400);
        $timesheet5->setFixedRate(84);
        $timesheet5->setUser($userKevin);
        $timesheet5->setActivity($activity);
        $timesheet5->setProject($project);
        $timesheet5->setBegin(new \DateTime('2021-03-12 12:13:00'));
        $timesheet5->setEnd(new \DateTime('2021-03-12 12:17:40'));
        $timesheet5->setDescription(
            "foo\n" .
            "foo\r\n" .
            'foo' . PHP_EOL .
            "bar\n" .
            "bar\r\n" .
            'Hello'
        );

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
        $customer->setCountry('DE');
        $customer->setCurrency('USD');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a test invoice template title');
        $template->setVat(19);
        $template->setLanguage('it');
        $template->setCustomer($customer);

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

        $userId = new \ReflectionProperty(User::class, 'id');
        $user1 = new User();
        $user1->setUserIdentifier('foo-bar');
        $user1->addPreference($pref1);
        $user1->addPreference($pref2);
        $userId->setValue($user1, 1);
        //$user1->method('getPreferenceValue')->willReturn('50');

        $timesheet = new Timesheet();
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user1);
        $timesheet->setActivity($activity);
        $timesheet->setProject($project);
        $timesheet->setBegin(new \DateTime('2020-08-12 18:00:00'));
        $timesheet->setEnd(new \DateTime('2021-03-12 18:30:00'));

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
