<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Export\Renderer\HtmlRenderer;
use App\Export\ServiceExport;
use App\Export\Timesheet\HtmlRenderer as HtmlExporter;
use App\Repository\ProjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;

/**
 * @covers \App\Export\ServiceExport
 */
class ServiceExportTest extends TestCase
{
    public function testEmptyObject()
    {
        $sut = new ServiceExport();

        self::assertEmpty($sut->getRenderer());
        self::assertNull($sut->getRendererById('default'));

        self::assertEmpty($sut->getTimesheetExporter());
        self::assertNull($sut->getTimesheetExporterById('default'));
    }

    public function testAddRenderer()
    {
        $sut = new ServiceExport();

        $renderer = new HtmlRenderer($this->createMock(Environment::class), new EventDispatcher(), $this->createMock(ProjectRepository::class));
        $sut->addRenderer($renderer);

        self::assertEquals(1, \count($sut->getRenderer()));
        self::assertSame($renderer, $sut->getRendererById('html'));
    }

    public function testAddTimesheetExporter()
    {
        $sut = new ServiceExport();

        $exporter = new HtmlExporter($this->createMock(Environment::class), new EventDispatcher());
        $sut->addTimesheetExporter($exporter);

        self::assertEquals(1, \count($sut->getTimesheetExporter()));
        self::assertSame($exporter, $sut->getTimesheetExporterById('print'));
    }
}
