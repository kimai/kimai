<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Activity\ActivityStatisticService;
use App\Export\ExportRepositoryInterface;
use App\Export\Renderer\HtmlRenderer;
use App\Export\ServiceExport;
use App\Export\Timesheet\HtmlRenderer as HtmlExporter;
use App\Project\ProjectStatisticService;
use App\Repository\Query\ExportQuery;
use App\Tests\Mocks\Export\HtmlRendererFactoryMock;
use App\Tests\Mocks\Export\PdfRendererFactoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

/**
 * @covers \App\Export\ServiceExport
 */
class ServiceExportTest extends TestCase
{
    private function createSut(): ServiceExport
    {
        return new ServiceExport(
            $this->createMock(EventDispatcherInterface::class),
            (new HtmlRendererFactoryMock($this))->create(),
            (new PdfRendererFactoryMock($this))->create(),
        );
    }

    public function testEmptyObject()
    {
        $sut = $this->createSut();

        self::assertEmpty($sut->getRenderer());
        self::assertNull($sut->getRendererById('default'));

        self::assertEmpty($sut->getTimesheetExporter());
        self::assertNull($sut->getTimesheetExporterById('default'));
    }

    public function testAddRenderer()
    {
        $sut = $this->createSut();

        $renderer = new HtmlRenderer(
            $this->createMock(Environment::class),
            new EventDispatcher(),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class)
        );
        $sut->addRenderer($renderer);

        self::assertEquals(1, \count($sut->getRenderer()));
        self::assertSame($renderer, $sut->getRendererById('html'));
    }

    public function testAddTimesheetExporter()
    {
        $sut = $this->createSut();

        $exporter = new HtmlExporter($this->createMock(Environment::class), new EventDispatcher(), $this->createMock(ProjectStatisticService::class), $this->createMock(ActivityStatisticService::class));
        $sut->addTimesheetExporter($exporter);

        self::assertEquals(1, \count($sut->getTimesheetExporter()));
        self::assertSame($exporter, $sut->getTimesheetExporterById('print'));
    }

    public function testAddExportRepository()
    {
        $sut = $this->createSut();

        $repository = $this->createMock(ExportRepositoryInterface::class);
        $repository->expects($this->once())->method('getExportItemsForQuery')->willReturn([]);
        $sut->addExportRepository($repository);

        $query = new ExportQuery();
        $items = $sut->getExportItems($query);

        self::assertEquals([], $items);
    }
}
