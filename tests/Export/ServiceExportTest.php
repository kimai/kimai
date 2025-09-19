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
use App\Export\Base\CsvRenderer;
use App\Export\Base\HtmlRenderer;
use App\Export\Base\XlsxRenderer;
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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

#[CoversClass(ServiceExport::class)]
class ServiceExportTest extends TestCase
{
    private function createSut(bool $withTemplates = false, int $failureCount = 1): ServiceExport
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
            $this->createMock(EventDispatcherInterface::class),
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
}
