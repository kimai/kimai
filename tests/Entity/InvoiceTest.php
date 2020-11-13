<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\ActivityMeta;
use App\Entity\Customer;
use App\Entity\CustomerMeta;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Invoice
 */
class InvoiceTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Invoice();
        self::assertNull($sut->getCreatedAt());
        self::assertNull($sut->getCurrency());
        self::assertNull($sut->getCustomer());
        self::assertNull($sut->getDueDate());
        self::assertEquals(30, $sut->getDueDays());
        self::assertNull($sut->getId());
        self::assertNull($sut->getInvoiceFilename());
        self::assertNull($sut->getInvoiceNumber());
        self::assertEquals(0.0, $sut->getTax());
        self::assertEquals(0.0, $sut->getTotal());
        self::assertNull($sut->getUser());
        self::assertEquals(0.0, $sut->getVat());
        self::assertTrue($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertFalse($sut->isPaid());
        self::assertFalse($sut->isOverdue());
    }

    public function testSetterAndGetter()
    {
        $date = new \DateTime('-2 months');
        $sut = new Invoice();

        $sut->setIsPending();
        self::assertFalse($sut->isNew());
        self::assertTrue($sut->isPending());
        self::assertFalse($sut->isPaid());

        $sut->setIsPaid();
        self::assertFalse($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertTrue($sut->isPaid());

        $sut->setIsNew();
        self::assertTrue($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertFalse($sut->isPaid());
        self::assertFalse($sut->isOverdue());

        $sut->setModel($this->getInvoiceModel($date));
        self::assertTrue($sut->isOverdue());

        self::assertEquals($date, $sut->getCreatedAt());
        self::assertEquals('USD', $sut->getCurrency());
        self::assertNotNull($sut->getCustomer());
        self::assertNotNull($sut->getDueDate());
        self::assertEquals(9, $sut->getDueDays());
        self::assertNull($sut->getId());
        self::assertNull($sut->getInvoiceFilename());
        self::assertEquals(date('ymd', $date->getTimestamp()), $sut->getInvoiceNumber());
        self::assertEquals(55.72, $sut->getTax());
        self::assertEquals(348.99, $sut->getTotal());
        self::assertNotNull($sut->getUser());
        self::assertEquals(19, $sut->getVat());
    }

    protected function getInvoiceModel(\DateTime $created): InvoiceModel
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
        $customer->setVatId('kjuo8967');

        $template = new InvoiceTemplate();
        $template->setTitle('a test invoice template title');
        $template->setVat(19);
        $template->setDueDays(9);

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
        $query->setActivity($activity);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());

        $model = new InvoiceModel(new DebugFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery($query);
        $model->setUser($user);
        $model->setInvoiceDate($created);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = new DateNumberGenerator();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }
}
