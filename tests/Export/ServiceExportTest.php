<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Activity\ActivityStatisticService;
use App\Entity\Customer;
use App\Entity\ExportTemplate;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Event\ExportItemsQueryEvent;
use App\Export\Base\CsvRenderer;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\XlsxRenderer;
use App\Export\ExportablePreviewResult;
use App\Export\ExportableRepositoryInterface;
use App\Export\ExportableResultInterface;
use App\Export\ExportableResultStatistic;
use App\Export\ExportRepositoryInterface;
use App\Export\ServiceExport;
use App\Export\TooManyItemsExportException;
use App\Project\ProjectStatisticService;
use App\Repository\ExportTemplateRepository;
use App\Repository\Query\ExportQuery;
use App\Tests\Mocks\Export\CsvRendererFactoryMock;
use App\Tests\Mocks\Export\HtmlRendererFactoryMock;
use App\Tests\Mocks\Export\PdfRendererFactoryMock;
use App\Tests\Mocks\Export\XlsxRendererFactoryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

#[CoversClass(ServiceExport::class)]
class ServiceExportTest extends TestCase
{
    private function createSut(bool $withTemplates = false, int $failureCount = 1, ?EventDispatcherInterface $eventDispatcher = null): ServiceExport
    {
        $repository = $this->createMock(ExportTemplateRepository::class);
        $templates = [];
        $logger = $this->createMock(LoggerInterface::class);

        if ($withTemplates) {
            $template1 = $this->createMock(ExportTemplate::class);
            $template1->method('getId')->willReturn(1);
            $template1->method('getTitle')->willReturn('CSV Test');
            $template1->method('getLanguage')->willReturn('de');
            $template1->method('getRenderer')->willReturn('csv');
            $template1->method('getColumns')->willReturn(['date', 'customer.name', 'duration', 'rate']);

            $template2 = $this->createMock(ExportTemplate::class);
            $template2->method('getId')->willReturn(2);
            $template2->method('getTitle')->willReturn('XLSX Test');
            $template2->method('getLanguage')->willReturn('it');
            $template2->method('getRenderer')->willReturn('xlsx');
            $template2->method('getColumns')->willReturn(['date', 'begin', 'duration', 'rate', 'user.name']);

            $template3 = $this->createMock(ExportTemplate::class);
            $template3->method('getTitle')->willReturn('XLSX Test');
            $template3->method('getLanguage')->willReturn('it');
            $template3->method('getRenderer')->willReturn('foo'); // invalid renderer will be ignored
            $template3->method('getColumns')->willReturn(['date', 'begin', 'duration', 'rate', 'user.name']);

            $logger->expects($this->exactly($failureCount))->method('error')->with('Unknown export template type: ' . $template3->getRenderer());

            $templates = [$template1, $template2, $template3];
        }

        $repository->method('findAll')->willReturn($templates);

        return new ServiceExport(
            $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
            (new HtmlRendererFactoryMock($this))->create(),
            (new PdfRendererFactoryMock($this))->create(),
            (new CsvRendererFactoryMock($this))->create(),
            (new XlsxRendererFactoryMock($this))->create(),
            $repository,
            $logger,
        );
    }

    public function testEmptyObject(): void
    {
        $sut = $this->createSut();

        self::assertCount(4, $sut->getRenderer());
        self::assertNull($sut->getRendererById('default'));

        self::assertCount(4, $sut->getTimesheetExporter());
        self::assertNull($sut->getTimesheetExporterById('default'));
    }

    public function testAddRenderer(): void
    {
        $sut = $this->createSut();

        $renderer = new HtmlRenderer(
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );
        $sut->addRenderer($renderer);

        self::assertEquals(5, \count($sut->getRenderer()));
        self::assertSame($renderer, $sut->getRendererById('html'));
    }

    public function testAddTimesheetExporter(): void
    {
        $sut = $this->createSut();

        self::assertEquals(4, \count($sut->getTimesheetExporter()));

        $exporter = new HtmlRenderer(
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class),
            'print'
        );
        $sut->addTimesheetExporter($exporter);

        self::assertEquals(5, \count($sut->getTimesheetExporter()));
        self::assertSame($exporter, $sut->getTimesheetExporterById('print'));
    }

    public function testAddExportRepository(): void
    {
        $sut = $this->createSut();

        $repository = $this->createMock(ExportRepositoryInterface::class);
        $repository->expects($this->once())->method('getExportItemsForQuery')->willReturn([]);
        $sut->addExportRepository($repository);

        $query = new ExportQuery();
        $items = $sut->getExportItems($query);

        self::assertEquals([], $items);
    }

    public function testWithTemplates(): void
    {
        $sut = $this->createSut(true, 2);

        $renderer = $sut->getRenderer();
        self::assertCount(6, $renderer);
        self::assertInstanceOf(CsvRenderer::class, $renderer[4]);
        self::assertInstanceOf(XlsxRenderer::class, $renderer[5]);
        self::assertInstanceOf(CsvRenderer::class, $sut->getRendererById('1'));
    }

    private function createTimesheet(Customer $customer, int $duration, float $rate): Timesheet
    {
        $project = new Project();
        $project->setCustomer($customer);

        $timesheet = new Timesheet();
        $timesheet->setProject($project);
        $timesheet->setDuration($duration);
        $timesheet->setRate($rate);

        return $timesheet;
    }

    private function createCustomer(int $id): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn($id);

        return $customer;
    }

    /**
     * A repository which supports the optimized preview: it must not be asked to load all of its items.
     */
    private function createExportableRepository(ExportQuery $query, int $expectedMaxItems, ExportableResultInterface $result): ExportRepositoryInterface
    {
        $repository = $this->createMockForIntersectionOfInterfaces([ExportRepositoryInterface::class, ExportableRepositoryInterface::class]);
        if ($expectedMaxItems > 0) {
            $repository->expects($this->once())->method('getPreview')->with(
                $query,
                $expectedMaxItems
            )->willReturn($result);
        } else {
            $repository->expects($this->never())->method('getPreview');
        }
        $repository->expects($this->never())->method('getExportItemsForQuery');
        self::assertInstanceOf(ExportRepositoryInterface::class, $repository);

        return $repository;
    }

    public function testGetExportItemsMergesAllRepositories(): void
    {
        $item1 = new Timesheet();
        $item2 = new Timesheet();
        $item3 = new Timesheet();

        $repository1 = $this->createMock(ExportRepositoryInterface::class);
        $repository1->expects($this->once())->method('getExportItemsForQuery')->willReturn([$item1, $item2]);
        $repository2 = $this->createMock(ExportRepositoryInterface::class);
        $repository2->expects($this->once())->method('getExportItemsForQuery')->willReturn([$item3]);

        $sut = $this->createSut();
        $sut->addExportRepository($repository1);
        $sut->addExportRepository($repository2);

        $query = new ExportQuery();
        // exactly reaching the limit is allowed
        $query->setMaxResults(3);

        self::assertSame([$item1, $item2, $item3], $sut->getExportItems($query));
    }

    public function testGetExportItemsThrowsIfAllRepositoriesTogetherExceedTheLimit(): void
    {
        $repository1 = $this->createMock(ExportRepositoryInterface::class);
        $repository1->method('getExportItemsForQuery')->willReturn([new Timesheet(), new Timesheet()]);
        $repository2 = $this->createMock(ExportRepositoryInterface::class);
        $repository2->method('getExportItemsForQuery')->willReturn([new Timesheet()]);

        $sut = $this->createSut();
        $sut->addExportRepository($repository1);
        $sut->addExportRepository($repository2);

        $query = new ExportQuery();
        $query->setMaxResults(2);

        $this->expectException(TooManyItemsExportException::class);
        $this->expectExceptionMessage('Limit reached! Found more than the allowed maximum of 2 items for export');

        $sut->getExportItems($query);
    }

    /**
     * @param positive-int $limit
     */
    private function createLimitingEventDispatcher(int $limit): EventDispatcher
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ExportItemsQueryEvent::class, function (ExportItemsQueryEvent $event) use ($limit): void {
            $event->getExportQuery()->setMaxResults($limit);
        });

        return $eventDispatcher;
    }

    public function testGetExportItemsIsNotLimitedByDefault(): void
    {
        $items = [new Timesheet(), new Timesheet(), new Timesheet()];

        $repository = $this->createMock(ExportRepositoryInterface::class);
        $repository->expects($this->once())->method('getExportItemsForQuery')->willReturnCallback(function (ExportQuery $query) use ($items): array {
            self::assertNull($query->getMaxResults());

            return $items;
        });

        $sut = $this->createSut();
        $sut->addExportRepository($repository);

        self::assertSame($items, $sut->getExportItems(new ExportQuery()));
    }

    public function testGetExportItemsThrowsIfTheLimitFromTheEventIsExceeded(): void
    {
        $repository = $this->createMock(ExportRepositoryInterface::class);
        $repository->method('getExportItemsForQuery')->willReturn([new Timesheet(), new Timesheet(), new Timesheet()]);

        $sut = $this->createSut(false, 1, $this->createLimitingEventDispatcher(2));
        $sut->addExportRepository($repository);

        $this->expectException(TooManyItemsExportException::class);
        $this->expectExceptionMessage('Limit reached! Found more than the allowed maximum of 2 items for export');

        $sut->getExportItems(new ExportQuery());
    }

    public function testGetExportPreviewShowsLessItemsIfTheEventLimitIsLower(): void
    {
        $query = new ExportQuery();

        $sut = $this->createSut(false, 1, $this->createLimitingEventDispatcher(3));
        $sut->addExportRepository($this->createExportableRepository($query, 3, new ExportablePreviewResult([], [])));

        $sut->getExportPreview($query, 3);

        self::assertNull($query->getMaxResults());
    }

    public function testGetExportPreviewKeepsItsLimitIfTheEventLimitIsHigher(): void
    {
        $query = new ExportQuery();

        $sut = $this->createSut(false, 1, $this->createLimitingEventDispatcher(1000));
        $sut->addExportRepository($this->createExportableRepository($query, 500, new ExportablePreviewResult([], [])));

        $sut->getExportPreview($query, 500);

        self::assertNull($query->getMaxResults());
    }

    public function testGetExportPreviewWithoutRepositories(): void
    {
        $preview = $this->createSut()->getExportPreview(new ExportQuery(), 10);

        self::assertSame([], $preview->getExportItems());
        self::assertSame([], $preview->getStatistic());
    }

    public function testGetExportPreviewSharesLimitBetweenRepositories(): void
    {
        $customer1 = $this->createCustomer(1);
        $customer2 = $this->createCustomer(2);
        $item1 = new Timesheet();
        $item2 = new Timesheet();
        $item3 = new Timesheet();
        $query = new ExportQuery();

        $sut = $this->createSut();
        $sut->addExportRepository($this->createExportableRepository($query, 5, new ExportablePreviewResult([$item1, $item2], [
            new ExportableResultStatistic($customer1, 100, 3600, 10.5),
        ])));
        // two of the five preview items are already used by the first repository
        $sut->addExportRepository($this->createExportableRepository($query, 3, new ExportablePreviewResult([$item3], [
            new ExportableResultStatistic($customer1, 1, 60, 1.5),
            new ExportableResultStatistic($customer2, 2, 120, 2.5),
        ])));
        // repositories are still asked if no items are left, because they contribute to the statistic
        $sut->addExportRepository($this->createExportableRepository($query, 2, new ExportablePreviewResult([], [])));

        $preview = $sut->getExportPreview($query, 5);

        self::assertSame([$item1, $item2, $item3], $preview->getExportItems());

        $statistic = $preview->getStatistic();
        self::assertCount(2, $statistic);
        self::assertSame($customer1, $statistic[0]->getCustomer());
        self::assertSame(101, $statistic[0]->getCount());
        self::assertSame(3660, $statistic[0]->getDuration());
        self::assertSame(12.0, $statistic[0]->getRate());
        self::assertSame($customer2, $statistic[1]->getCustomer());
        self::assertSame(2, $statistic[1]->getCount());
    }

    #[Group('legacy')]
    public function testGetExportPreviewSupportsRepositoriesWithoutPreview(): void
    {
        $customer = $this->createCustomer(1);
        $item1 = $this->createTimesheet($customer, 100, 1.5);
        $item2 = $this->createTimesheet($customer, 200, 2.5);
        $item3 = $this->createTimesheet($customer, 300, 3.5);
        $query = new ExportQuery();

        $legacy = $this->createMock(ExportRepositoryInterface::class);
        $legacy->expects($this->once())->method('getExportItemsForQuery')->with($query)->willReturn([$item1, $item2, $item3]);
        $legacy->method('getType')->willReturn('legacy');

        $sut = $this->createSut();
        $sut->addExportRepository($legacy);
        // the limit was fully used by the first repository
        $sut->addExportRepository($this->createExportableRepository($query, 0, new ExportablePreviewResult([], [])));

        $preview = $sut->getExportPreview($query, 2);

        // only the items are limited: the statistic must cover all of them
        self::assertSame([$item1, $item2], $preview->getExportItems());
        $statistic = $preview->getStatistic();
        self::assertCount(1, $statistic);
        self::assertSame($customer, $statistic[0]->getCustomer());
        self::assertSame(3, $statistic[0]->getCount());
        self::assertSame(600, $statistic[0]->getDuration());
        self::assertSame(7.5, $statistic[0]->getRate());
    }

    public function testSetExportedIsForwardedToAllRepositories(): void
    {
        $items = [new Timesheet()];

        $repository1 = $this->createMock(ExportRepositoryInterface::class);
        $repository1->expects($this->once())->method('setExported')->with($items);
        $repository2 = $this->createMock(ExportRepositoryInterface::class);
        $repository2->expects($this->once())->method('setExported')->with($items);

        $sut = $this->createSut();
        $sut->addExportRepository($repository1);
        $sut->addExportRepository($repository2);

        $sut->setExported($items);
    }

    public function testGetMaximumResultsWithoutLimitIsUnlimited(): void
    {
        self::assertNull($this->createSut()->getMaximumResults(new ExportQuery()));
    }

    public function testGetMaximumResultsUsesLimitFromQuery(): void
    {
        $query = new ExportQuery();
        $query->setMaxResults(10);

        self::assertSame(10, $this->createSut()->getMaximumResults($query));
    }

    public function testGetMaximumResultsCanBeChangedByEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(ExportItemsQueryEvent::class, function (ExportItemsQueryEvent $event): void {
            $event->getExportQuery()->setMaxResults(5);
        });

        $sut = $this->createSut(false, 1, $eventDispatcher);

        self::assertSame(5, $sut->getMaximumResults(new ExportQuery()));
    }
}
