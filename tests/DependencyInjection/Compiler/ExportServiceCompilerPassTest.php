<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\ExportServiceCompilerPass;
use App\Export\Renderer\CsvRenderer;
use App\Export\Renderer\HtmlRenderer;
use App\Export\ServiceExport;
use App\Export\Timesheet\PDFRenderer;
use App\Export\Timesheet\XlsxRenderer;
use App\Export\TimesheetExportRepository;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \App\DependencyInjection\Compiler\ExportServiceCompilerPass
 */
class ExportServiceCompilerPassTest extends TestCase
{
    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kimai.export.documents', [
            'templates/export/renderer/',
        ]);

        $definition = new Definition(ServiceExport::class);
        $container->setDefinition(ServiceExport::class, $definition);

        $renderers = [CsvRenderer::class, HtmlRenderer::class];
        foreach ($renderers as $renderer) {
            $container->register($renderer)->addTag(Kernel::TAG_EXPORT_RENDERER);
        }

        $exporters = [PDFRenderer::class, XlsxRenderer::class];
        foreach ($exporters as $exporter) {
            $container->register($exporter)->addTag(Kernel::TAG_TIMESHEET_EXPORTER);
        }

        $repositories = [TimesheetExportRepository::class];
        foreach ($repositories as $repository) {
            $container->register($repository)->addTag(Kernel::TAG_EXPORT_REPOSITORY);
        }

        return $container;
    }

    public function testCallsAreAdded(): void
    {
        $container = $this->getContainer();
        $sut = new ExportServiceCompilerPass();
        $sut->process($container);

        $definition = $container->findDefinition(ServiceExport::class);
        $methods = $definition->getMethodCalls();

        self::assertCount(6, $methods);
        self::assertTrue($definition->hasMethodCall('addDirectory'));
        self::assertTrue($definition->hasMethodCall('addRenderer'));
        self::assertTrue($definition->hasMethodCall('addTimesheetExporter'));
        self::assertTrue($definition->hasMethodCall('addExportRepository'));
    }
}
