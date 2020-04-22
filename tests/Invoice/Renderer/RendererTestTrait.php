<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Configuration\LanguageFormattings;
use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\InvoiceDocument;
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
use App\Invoice\Renderer\AbstractRenderer;
use App\Repository\Query\InvoiceQuery;
use App\Twig\DateExtensions;
use App\Twig\Extensions;
use App\Utils\LocaleSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

trait RendererTestTrait
{
    /**
     * @return string
     */
    protected function getInvoiceTemplatePath()
    {
        return __DIR__ . '/../../../templates/invoice/renderer/';
    }

    /**
     * @param string $filename
     * @return InvoiceDocument
     */
    protected function getInvoiceDocument(string $filename, bool $testOnly = false)
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
     * @param string $classname
     * @return AbstractRenderer
     */
    protected function getAbstractRenderer(string $classname)
    {
        return new $classname();
    }

    protected function getFormatter(): InvoiceFormatter
    {
        $requestStack = new RequestStack();
        $languages = [
            'en' => [
                'date' => 'Y.m.d',
                'duration' => '%h:%m h',
                'time' => 'H:i',
            ]
        ];

        $request = new Request();
        $request->setLocale('en');
        $requestStack->push($request);

        $localeSettings = new LocaleSettings($requestStack, new LanguageFormattings($languages));

        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $dateExtension = new DateExtensions($localeSettings);
        $extensions = new Extensions($localeSettings);

        return new DefaultInvoiceFormatter($translator, $dateExtension, $extensions);
    }

    protected function getInvoiceModel(): InvoiceModel
    {
        $user = new User();
        $user->setUsername('one-user');
        $user->setTitle('user title');
        $user->setAlias('genious alias');
        $user->setEmail('fantastic@four');
        $user->addPreference((new UserPreference())->setName('kitty')->setValue('kat'));
        $user->addPreference((new UserPreference())->setName('hello')->setValue('world'));

        $customer = new Customer();
        $customer->setName('customer,with/special#name');
        $customer->setAddress('Foo' . PHP_EOL . 'Street' . PHP_EOL . '1111 City');
        $customer->setCurrency('EUR');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a very *long* test invoice / template title with [special] character');
        $template->setVat(19);
        $template->setLanguage('en');

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setMetaField((new ProjectMeta())->setName('foo-project')->setValue('bar-project')->setIsVisible(true));

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('foo-activity')->setValue('bar-activity')->setIsVisible(true));

        $project2 = new Project();
        $project2->setName('project 2 name');
        $project2->setCustomer($customer);
        $project2->setMetaField((new ProjectMeta())->setName('foo-project')->setValue('bar-project2')->setIsVisible(true));

        $activity2 = new Activity();
        $activity2->setName('activity 1 description');
        $activity2->setProject($project2);
        $activity2->setMetaField((new ActivityMeta())->setName('foo-activity')->setValue('bar-activity2')->setIsVisible(true));

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');

        $user2 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
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
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet')->setIsVisible(true));

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet'))
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet2')->setValue('bar-timesheet2')->setIsVisible(true))
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity2)
            ->setProject($project2)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet')->setValue('bar-timesheet1')->setIsVisible(true))
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
            ->setMetaField((new TimesheetMeta())->setName('foo-timesheet3')->setValue('bluuuub')->setIsVisible(true))
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

        $query = new InvoiceQuery();
        $query->setActivities([$activity, $activity2]);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());
        $query->setProjects([$project, $project2]);

        $model = new InvoiceModel($this->getFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery($query);
        $model->setUser($user);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = new DateNumberGenerator();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }

    protected function getInvoiceModelOneEntry(): InvoiceModel
    {
        $user = new User();
        $user->setUsername('one-user');
        $user->setTitle('user title');
        $user->setAlias('genious alias');
        $user->setEmail('fantastic@four');
        $user->addPreference((new UserPreference())->setName('kitty')->setValue('kat'));
        $user->addPreference((new UserPreference())->setName('hello')->setValue('world'));

        $customer = new Customer();
        $customer->setName('customer,with/special#name');
        $customer->setCurrency('USD');
        $customer->setMetaField((new CustomerMeta())->setName('foo-customer')->setValue('bar-customer')->setIsVisible(true));

        $template = new InvoiceTemplate();
        $template->setTitle('a test invoice template title');
        $template->setVat(19);

        $project = new Project();
        $project->setName('project name');
        $project->setCustomer($customer);
        $project->setMetaField((new ProjectMeta())->setName('foo-project')->setValue('bar-project')->setIsVisible(true));

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);
        $activity->setMetaField((new ActivityMeta())->setName('foo-activity')->setValue('bar-activity')->setIsVisible(true));

        $userMethods = ['getId', 'getPreferenceValue', 'getUsername'];
        $user1 = $this->getMockBuilder(User::class)->onlyMethods($userMethods)->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);
        $user1->method('getPreferenceValue')->willReturn('50');
        $user1->method('getUsername')->willReturn('foo-bar');

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

        $entries = [$timesheet];

        $query = new InvoiceQuery();
        $query->addActivity($activity);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());

        $model = new InvoiceModel($this->getFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery($query);
        $model->setUser($user);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = new DateNumberGenerator();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }
}
