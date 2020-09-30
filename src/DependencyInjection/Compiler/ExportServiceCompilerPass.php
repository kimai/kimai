<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Export\Renderer\HtmlRenderer;
use App\Export\Renderer\HtmlRendererFactory;
use App\Export\Renderer\PdfRendererFactory;
use App\Export\ServiceExport;
use App\Kernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Dynamically adds all dependencies to the ExportService.
 */
class ExportServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(ServiceExport::class);

        $taggedRenderer = $container->findTaggedServiceIds(Kernel::TAG_EXPORT_RENDERER);
        foreach ($taggedRenderer as $id => $tags) {
            $definition->addMethodCall('addRenderer', [new Reference($id)]);
        }

        $taggedExporter = $container->findTaggedServiceIds(Kernel::TAG_TIMESHEET_EXPORTER);
        foreach ($taggedExporter as $id => $tags) {
            $definition->addMethodCall('addTimesheetExporter', [new Reference($id)]);
        }

        $path = \dirname(\dirname(\dirname(__DIR__))) . DIRECTORY_SEPARATOR;
        foreach ($container->getParameter('kimai.export.documents') as $exportPath) {
            if (!is_dir($path . $exportPath)) {
                continue;
            }

            foreach (glob($path . $exportPath . '/*.html.twig') as $htmlTpl) {
                $tplName = basename($htmlTpl);

                $serviceId = 'exporter_renderer.' . str_replace('.', '_', $tplName);

                $factoryDefinition = new Definition(HtmlRenderer::class);
                $factoryDefinition->addArgument($tplName);
                $factoryDefinition->addArgument($tplName);
                $factoryDefinition->setFactory([new Reference(HtmlRendererFactory::class), 'create']);

                $container->setDefinition($serviceId, $factoryDefinition);
                $definition->addMethodCall('addRenderer', [new Reference($serviceId)]);
            }

            foreach (glob($path . $exportPath . '/*.pdf.twig') as $pdfHtml) {
                $tplName = basename($pdfHtml);

                $serviceId = 'exporter_renderer.' . str_replace('.', '_', $tplName);

                $factoryDefinition = new Definition(HtmlRenderer::class);
                $factoryDefinition->addArgument($tplName);
                $factoryDefinition->addArgument($tplName);
                $factoryDefinition->setFactory([new Reference(PdfRendererFactory::class), 'create']);

                $container->setDefinition($serviceId, $factoryDefinition);
                $definition->addMethodCall('addRenderer', [new Reference($serviceId)]);
            }
        }
    }
}
