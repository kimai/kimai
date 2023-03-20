<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Configuration\LocaleService;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\Renderer\TwigRenderer;
use App\Invoice\ServiceInvoice;
use App\Model\InvoiceDocument;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use App\Utils\FileHelper;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @covers \App\Invoice\ServiceInvoice
 */
class ServiceInvoiceTest extends TestCase
{
    private function getSut(array $paths): ServiceInvoice
    {
        $languages = [
            'en' => [
                'date' => 'Y.m.d',
                'duration' => '%h:%m',
                'time' => 'H:i',
            ]
        ];

        $formattings = new LocaleService($languages);

        $repo = new InvoiceDocumentRepository($paths);
        $invoiceRepo = $this->createMock(InvoiceRepository::class);

        return new ServiceInvoice($repo, new FileHelper(realpath(__DIR__ . '/../../var/data/')), $invoiceRepo, $formattings, (new InvoiceModelFactoryFactory($this))->create());
    }

    public function testInvalidExceptionOnChangeState(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown invoice status');
        $sut = $this->getSut([]);
        $sut->changeInvoiceStatus(new Invoice(), 'foo');
    }

    public function testEmptyObject(): void
    {
        $sut = $this->getSut([]);

        $this->assertEmpty($sut->getCalculator());
        $this->assertIsArray($sut->getCalculator());
        $this->assertEmpty($sut->getRenderer());
        $this->assertIsArray($sut->getRenderer());
        $this->assertEmpty($sut->getNumberGenerator());
        $this->assertIsArray($sut->getNumberGenerator());
        $this->assertEmpty($sut->getDocuments());
        $this->assertIsArray($sut->getDocuments());

        $this->assertNull($sut->getCalculatorByName('default'));
        $this->assertNull($sut->getDocumentByName('default'));
        $this->assertNull($sut->getNumberGeneratorByName('default'));
    }

    public function testWithDocumentDirectory(): void
    {
        $sut = $this->getSut(['templates/invoice/renderer/']);

        $actual = $sut->getDocuments();
        $this->assertNotEmpty($actual);
        foreach ($actual as $document) {
            $this->assertInstanceOf(InvoiceDocument::class, $document);
        }

        $actual = $sut->getDocumentByName('default');
        $this->assertInstanceOf(InvoiceDocument::class, $actual);
    }

    public function testAdd(): void
    {
        $sut = $this->getSut([]);

        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator($this->getNumberGeneratorSut());
        $twig = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $sut->addRenderer(new TwigRenderer($twig));

        $this->assertEquals(1, \count($sut->getCalculator()));
        $this->assertInstanceOf(DefaultCalculator::class, $sut->getCalculatorByName('default'));

        $this->assertEquals(1, \count($sut->getNumberGenerator()));
        $this->assertInstanceOf(DateNumberGenerator::class, $sut->getNumberGeneratorByName('date'));

        $this->assertEquals(1, \count($sut->getRenderer()));
    }

    public function testCreateModelThrowsOnMissingTemplate(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot create invoice model without template');

        $query = new InvoiceQuery();
        $query->setCustomers([new Customer('foo')]);

        $sut = $this->getSut([]);
        $sut->createModel($query);
    }

    public function testCreateModelUsesTemplateLanguage(): void
    {
        $template = new InvoiceTemplate();
        $template->setNumberGenerator('date');
        $template->setLanguage('it');

        $query = new InvoiceQuery();
        $query->setCustomers([new Customer('foo')]);
        $query->setTemplate($template);

        $sut = $this->getSut([]);
        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator($this->getNumberGeneratorSut());

        $model = $sut->createModel($query);

        self::assertEquals('it', $model->getTemplate()->getLanguage());
    }

    public function testBeginAndEndDateFallback(): void
    {
        $timezone = new \DateTimeZone('Europe/Vienna');
        $customer = new Customer('foo');
        $project = new Project();
        $project->setCustomer($customer);

        $timesheet1 = new Timesheet();
        $timesheet1->setProject($project);
        $timesheet1->setBegin(new \DateTime('2011-01-27 12:12:12', $timezone));
        $timesheet1->setEnd(new \DateTime('2020-01-27 12:12:12', $timezone));

        $timesheet2 = new Timesheet();
        $timesheet2->setProject($project);
        $timesheet2->setBegin(new \DateTime('2010-01-27 08:24:33', $timezone));
        $timesheet2->setEnd(new \DateTime('2019-01-27 12:12:12', $timezone));

        $timesheet3 = new Timesheet();
        $timesheet3->setProject($project);
        $timesheet3->setBegin(new \DateTime('2019-01-27 12:12:12', $timezone));
        $timesheet3->setEnd(new \DateTime('2020-01-07 12:12:12', $timezone));

        $timesheet4 = new Timesheet();
        $timesheet4->setProject($project);
        $timesheet4->setBegin(new \DateTime('2020-01-27 10:12:12', $timezone));
        $timesheet4->setEnd(new \DateTime('2020-11-27 11:12:12', $timezone));

        $timesheet5 = new Timesheet();
        $timesheet5->setProject($project);
        $timesheet5->setBegin(new \DateTime('2012-01-27 12:12:12', $timezone));
        $timesheet5->setEnd(new \DateTime('2018-01-27 12:12:12', $timezone));

        $repo = $this->createMock(InvoiceItemRepositoryInterface::class);
        $repo->method('getInvoiceItemsForQuery')->willReturn([
            $timesheet1,
            $timesheet2,
            $timesheet3,
            $timesheet4,
            $timesheet5,
        ]);

        $template = new InvoiceTemplate();
        $template->setNumberGenerator('date');
        $template->setLanguage('de');

        $query = new InvoiceQuery();
        $query->setCustomers([new Customer('foo'), $customer]);
        $query->setTemplate($template);
        self::assertNull($query->getBegin());
        self::assertNull($query->getEnd());

        $sut = $this->getSut([]);
        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator($this->getNumberGeneratorSut());

        $sut->addInvoiceItemRepository($repo);

        $sut->createModels($query);

        self::assertNotNull($query->getBegin());
        self::assertNotNull($query->getEnd());

        self::assertEquals('2010-01-27T00:00:00+0100', $query->getBegin()->format(DATE_ISO8601));
        self::assertEquals('2020-11-27T23:59:59+0100', $query->getEnd()->format(DATE_ISO8601));
    }

    public function testCreateModelsIncludesModelsWithNegativeTotal(): void
    {
        $timezone = new \DateTimeZone('Europe/Vienna');

        $customer1 = $this->createMock(Customer::class);
        $customer1->method('getId')->willReturn(1);
        $customer1->method('getName')->willReturn('customer1');
        $customer1->method('isVisible')->willReturn(true);
        $project1 = new Project();
        $project1->setCustomer($customer1);

        $customer2 = $this->createMock(Customer::class);
        $customer2->method('getId')->willReturn(2);
        $customer2->method('getName')->willReturn('customer2');
        $customer2->method('isVisible')->willReturn(true);
        $project2 = new Project();
        $project2->setCustomer($customer2);

        $customer3 = $this->createMock(Customer::class);
        $customer3->method('getId')->willReturn(3);
        $customer3->method('getName')->willReturn('customer3');
        $customer3->method('isVisible')->willReturn(true);
        $project3 = new Project();
        $project3->setCustomer($customer3);

        $customer4 = $this->createMock(Customer::class);
        $customer4->method('getId')->willReturn(4);
        $customer4->method('getName')->willReturn('customer4');
        $customer4->method('isVisible')->willReturn(true);
        $project4 = new Project();
        $project4->setCustomer($customer4);

        $timesheet1 = new Timesheet();
        $timesheet1->setProject($project1);
        $timesheet1->setBegin(new \DateTime('2011-01-27 11:11:11', $timezone));
        $timesheet1->setEnd(new \DateTime('2020-01-27 12:12:12', $timezone));
        $timesheet1->setRate(-20.01);

        $timesheet2 = new Timesheet();
        $timesheet2->setProject($project1);
        $timesheet2->setBegin(new \DateTime('2010-01-28 11:11:11', $timezone));
        $timesheet2->setEnd(new \DateTime('2019-01-28 12:12:12', $timezone));
        $timesheet2->setRate(20.0);

        $timesheet3 = new Timesheet();
        $timesheet3->setProject($project2);
        $timesheet3->setBegin(new \DateTime('2019-01-27 22:22:22', $timezone));
        $timesheet3->setEnd(new \DateTime('2020-01-07 23:23:23', $timezone));
        $timesheet3->setRate(100);

        $timesheet4 = new Timesheet();
        $timesheet4->setProject($project2);
        $timesheet4->setBegin(new \DateTime('2019-01-28 22:22:22', $timezone));
        $timesheet4->setEnd(new \DateTime('2020-01-08 23:22:22', $timezone));
        $timesheet4->setRate(-200);

        $timesheet5 = new Timesheet();
        $timesheet5->setProject($project3);
        $timesheet5->setBegin(new \DateTime('2012-01-27 12:12:12', $timezone));
        $timesheet5->setEnd(new \DateTime('2018-01-27 12:12:12', $timezone));
        $timesheet5->setRate(1.73);

        $timesheet6 = new Timesheet();
        $timesheet6->setProject($project4);
        $timesheet6->setBegin(new \DateTime('2011-01-27 11:11:11', $timezone));
        $timesheet6->setEnd(new \DateTime('2020-01-27 12:12:12', $timezone));
        $timesheet6->setRate(-20.0);

        $timesheet7 = new Timesheet();
        $timesheet7->setProject($project4);
        $timesheet7->setBegin(new \DateTime('2010-01-28 11:11:11', $timezone));
        $timesheet7->setEnd(new \DateTime('2019-01-28 12:12:12', $timezone));
        $timesheet7->setRate(20.0);

        $repo = $this->createMock(InvoiceItemRepositoryInterface::class);
        $repo->method('getInvoiceItemsForQuery')->willReturn([
            $timesheet1,
            $timesheet2,
            $timesheet3,
            $timesheet4,
            $timesheet5,
            $timesheet6,
            $timesheet7,
        ]);

        $template = new InvoiceTemplate();
        $template->setNumberGenerator('date');
        $template->setLanguage('de');

        self::assertEquals('de', $template->getLanguage());

        $query = new InvoiceQuery();
        $query->setCustomers([$customer3, new Customer('tröööt'), $customer1, $customer3]);
        $query->setTemplate($template);
        self::assertNull($query->getBegin());
        self::assertNull($query->getEnd());

        $sut = $this->getSut([]);
        $sut->addCalculator(new DefaultCalculator());
        $sut->addNumberGenerator($this->getNumberGeneratorSut());
        $sut->addInvoiceItemRepository($repo);
        $models = $sut->createModels($query);

        self::assertCount(4, $models);
        self::assertInstanceOf(InvoiceModel::class, $models[0]);
        self::assertEquals(-0.01, $models[0]->getCalculator()->getTotal());
        self::assertInstanceOf(InvoiceModel::class, $models[1]);
        self::assertEquals(-100, $models[1]->getCalculator()->getTotal());
        self::assertInstanceOf(InvoiceModel::class, $models[2]);
        self::assertEquals(1.73, $models[2]->getCalculator()->getTotal());
        self::assertInstanceOf(InvoiceModel::class, $models[3]);
        self::assertEquals(0.0, $models[3]->getCalculator()->getTotal());
    }

    private function getNumberGeneratorSut(): DateNumberGenerator
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('hasInvoice')
            ->willReturn(false);

        return new DateNumberGenerator($repository);
    }
}
