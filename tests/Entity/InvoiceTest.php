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
use App\Entity\InvoiceMeta;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Invoice
 */
class InvoiceTest extends TestCase
{
    public function testDefaultValues(): void
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
        self::assertNull($sut->getPaymentDate());
        self::assertNull($sut->getComment());
    }

    public function testSetInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown invoice status');

        $sut = new Invoice();
        $sut->setStatus('foo');
    }

    public function testSetterAndGetter(): void
    {
        $date = new \DateTime('-2 months');
        $sut = new Invoice();

        $sut->setIsPending();
        self::assertFalse($sut->isNew());
        self::assertTrue($sut->isPending());
        self::assertFalse($sut->isPaid());
        self::assertFalse($sut->isCanceled());
        self::assertEquals(Invoice::STATUS_PENDING, $sut->getStatus());

        $sut->setIsPaid();
        self::assertFalse($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertTrue($sut->isPaid());
        self::assertFalse($sut->isCanceled());
        self::assertEquals(Invoice::STATUS_PAID, $sut->getStatus());

        $sut->setStatus(Invoice::STATUS_PENDING);
        self::assertTrue($sut->isPending());
        self::assertEquals(Invoice::STATUS_PENDING, $sut->getStatus());

        $sut->setIsCanceled();
        self::assertFalse($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertFalse($sut->isPaid());
        self::assertFalse($sut->isOverdue());
        self::assertTrue($sut->isCanceled());
        self::assertEquals(Invoice::STATUS_CANCELED, $sut->getStatus());

        $sut->setIsNew();
        self::assertTrue($sut->isNew());
        self::assertFalse($sut->isPending());
        self::assertFalse($sut->isPaid());
        self::assertFalse($sut->isOverdue());
        self::assertFalse($sut->isCanceled());
        self::assertEquals(Invoice::STATUS_NEW, $sut->getStatus());

        $paymentDate = new \DateTime();
        $sut->setPaymentDate($paymentDate);
        self::assertEquals($paymentDate, $sut->getPaymentDate());
        $sut->setIsPending();
        self::assertNull($sut->getPaymentDate());
        $sut->setPaymentDate($paymentDate);
        $sut->setIsNew();
        self::assertNull($sut->getPaymentDate());

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
        self::assertEquals(293.27, $sut->getSubtotal());
        self::assertEquals(55.72, $sut->getTax());
        self::assertEquals(348.99, $sut->getTotal());
        self::assertNotNull($sut->getUser());
        self::assertEquals(19, $sut->getVat());

        $sut->setComment('foo bar');
        self::assertEquals('foo bar', $sut->getComment());
    }

    protected function getInvoiceModel(\DateTime $created): InvoiceModel
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
        $query->addActivity($activity);
        $query->setBegin(new \DateTime());
        $query->setEnd(new \DateTime());

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);
        $model->setUser($user);
        $model->setInvoiceDate($created);

        $calculator = new DefaultCalculator();
        $calculator->setModel($model);

        $model->setCalculator($calculator);

        $numberGenerator = $this->getNumberGeneratorSut();
        $numberGenerator->setModel($model);

        $model->setNumberGenerator($numberGenerator);

        return $model;
    }

    private function getNumberGeneratorSut()
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('hasInvoice')
            ->willReturn(false);

        return new DateNumberGenerator($repository);
    }

    public function testClone(): void
    {
        $sut = new Invoice();
        $sut->setComment('foo kajsdhgf aksjdhfg');
        $sut->setFilename('1234567890');

        $meta = new InvoiceMeta();
        $meta->setName('blabla');
        $meta->setValue('1234567890');
        $meta->setIsVisible(false);
        $meta->setIsRequired(true);
        $sut->setMetaField($meta);

        $clone = clone $sut;

        foreach ($sut->getMetaFields() as $metaField) {
            $cloneMeta = $clone->getMetaField($metaField->getName());
            self::assertEquals($cloneMeta->getValue(), $metaField->getValue());
        }
        self::assertEquals('1234567890', $clone->getInvoiceFilename());
        self::assertEquals('foo kajsdhgf aksjdhfg', $clone->getComment());
    }

    public function testMetaFields(): void
    {
        $sut = new Invoice();

        $meta = new InvoiceMeta();
        $meta->setName('foo')->setValue('bar2')->setType('test');
        self::assertInstanceOf(Invoice::class, $sut->setMetaField($meta));
        self::assertEquals(1, $sut->getMetaFields()->count());
        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test', $result->getType());
        self::assertEquals('bar2', $result->getValue());

        $meta2 = new InvoiceMeta();
        $meta2->setName('foo')->setValue('bar')->setType('test2');
        self::assertInstanceOf(Invoice::class, $sut->setMetaField($meta2));
        self::assertEquals(1, $sut->getMetaFields()->count());
        self::assertCount(0, $sut->getVisibleMetaFields());

        $result = $sut->getMetaField('foo');
        self::assertSame($result, $meta);
        self::assertEquals('test2', $result->getType());

        $sut->setMetaField((new InvoiceMeta())->setName('blub')->setIsVisible(true));
        $sut->setMetaField((new InvoiceMeta())->setName('blab')->setIsVisible(true));
        self::assertEquals(3, $sut->getMetaFields()->count());
        self::assertCount(2, $sut->getVisibleMetaFields());
    }
}
