<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Activity\ActivityStatisticService;
use App\Entity\ExportTemplate;
use App\Entity\MetaTableTypeInterface;
use App\Entity\TimesheetMeta;
use App\Export\Base\CsvRenderer;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\XlsxRenderer;
use App\Export\ExportPreviewColumnProviderInterface;
use App\Export\ExportRepositoryInterface;
use App\Export\ServiceExport;
use App\Project\ProjectStatisticService;
use App\Repository\ExportTemplateRepository;
use App\Repository\Query\ExportQuery;
use App\Tests\Mocks\Export\CsvRendererFactoryMock;
use App\Tests\Mocks\Export\HtmlRendererFactoryMock;
use App\Tests\Mocks\Export\PdfRendererFactoryMock;
use App\Tests\Mocks\Export\XlsxRendererFactoryMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[CoversClass(ServiceExport::class)]
class ServiceExportTest extends TestCase
{
    private function createSut(
        bool $withTemplates = false,
        int $failureCount = 1,
        (LoggerInterface&MockObject)|null $logger = null,
        ?TranslatorInterface $translator = null,
    ): ServiceExport
    {
        $repository = $this->createMock(ExportTemplateRepository::class);
        $templates = [];
        $logger ??= $this->createMock(LoggerInterface::class);
        $translator ??= $this->createMock(TranslatorInterface::class);

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
            $this->createMock(EventDispatcherInterface::class),
            (new HtmlRendererFactoryMock($this))->create(),
            (new PdfRendererFactoryMock($this))->create(),
            (new CsvRendererFactoryMock($this))->create(),
            (new XlsxRendererFactoryMock($this))->create(),
            $repository,
            $logger,
            $translator,
        );
    }

    /**
     * @param MetaTableTypeInterface[] $fields
     * @return ExportRepositoryInterface&ExportPreviewColumnProviderInterface
     */
    private function createPreviewRepository(string $type, array $fields): ExportRepositoryInterface&ExportPreviewColumnProviderInterface
    {
        $repository = $this->createMockForIntersectionOfInterfaces([
            ExportRepositoryInterface::class,
            ExportPreviewColumnProviderInterface::class,
        ]);
        $repository->method('getType')->willReturn($type);
        $repository->method('getExportPreviewColumns')->willReturn($fields);

        return $repository;
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

    public function testGetExportPreviewColumnsIgnoresRepositoriesWithoutCapability(): void
    {
        $sut = $this->createSut();
        $sut->addExportRepository($this->createMock(ExportRepositoryInterface::class));

        self::assertSame([], $sut->getExportPreviewColumns(new ExportQuery()));
    }

    public function testGetExportPreviewColumnsUsesCollisionSafeKeysAndStableOrder(): void
    {
        $firstField = (new TimesheetMeta())->setName('baz')->setLabel('First');
        $secondField = (new TimesheetMeta())->setName('bar_baz')->setLabel('Second');
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $sut = $this->createSut(translator: $translator);
        $sut->addExportRepository($this->createPreviewRepository('foo_bar', [$firstField]));
        $sut->addExportRepository($this->createPreviewRepository('foo', [$secondField]));

        $columns = $sut->getExportPreviewColumns(new ExportQuery());

        self::assertCount(2, $columns);
        self::assertSame('foo_bar', $columns[0]->getRepositoryType());
        self::assertSame('baz', $columns[0]->getName());
        self::assertSame('First', $columns[0]->getLabel());
        self::assertSame($firstField, $columns[0]->getField());
        self::assertSame('mf_666f6f5f626172_62617a', $columns[0]->getTableKey());
        self::assertSame('mf_666f6f_6261725f62617a', $columns[1]->getTableKey());
        self::assertNotSame($columns[0]->getTableKey(), $columns[1]->getTableKey());
    }

    public function testGetExportPreviewColumnsIgnoresFieldsWithoutNameAndKeepsFirstDuplicate(): void
    {
        $unnamed = new TimesheetMeta();
        $first = (new TimesheetMeta())->setName('place')->setLabel('First');
        $duplicate = (new TimesheetMeta())->setName('place')->setLabel('Second');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $sut = $this->createSut(logger: $logger, translator: $translator);
        $sut->addExportRepository($this->createPreviewRepository('timesheet', [$unnamed, $first, $duplicate]));

        $columns = $sut->getExportPreviewColumns(new ExportQuery());

        self::assertCount(1, $columns);
        self::assertSame($first, $columns[0]->getField());
        self::assertSame('First', $columns[0]->getLabel());
    }

    public function testGetExportPreviewColumnsDisambiguatesTranslatedLabels(): void
    {
        $first = (new TimesheetMeta())->setName('first')->setLabel('First raw label');
        $fallback = (new TimesheetMeta())->setName('fallback');
        $second = (new TimesheetMeta())->setName('second')->setLabel('Second raw label');
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static function (string $label): string {
            return match ($label) {
                'First raw label', 'Second raw label' => 'Shared label',
                'fallback' => 'Fallback label',
                default => $label,
            };
        });

        $sut = $this->createSut(translator: $translator);
        $sut->addExportRepository($this->createPreviewRepository('timesheet', [$first, $fallback]));
        $sut->addExportRepository($this->createPreviewRepository('expense', [$second]));

        $columns = $sut->getExportPreviewColumns(new ExportQuery());

        self::assertSame('timesheet: Shared label', $columns[0]->getLabel());
        self::assertSame('Fallback label', $columns[1]->getLabel());
        self::assertSame('expense: Shared label', $columns[2]->getLabel());
    }

    public function testGetExportPreviewColumnsKeepsLabelsWithinOneRepository(): void
    {
        $first = (new TimesheetMeta())->setName('first')->setLabel('Shared label');
        $second = (new TimesheetMeta())->setName('second')->setLabel('Shared label');
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $sut = $this->createSut(translator: $translator);
        $sut->addExportRepository($this->createPreviewRepository('timesheet', [$first, $second]));

        $columns = $sut->getExportPreviewColumns(new ExportQuery());

        self::assertSame('Shared label', $columns[0]->getLabel());
        self::assertSame('Shared label', $columns[1]->getLabel());
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
}
