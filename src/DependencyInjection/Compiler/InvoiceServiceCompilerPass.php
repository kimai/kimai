<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Invoice\ServiceInvoice;
use App\Kernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Dynamically adds all dependencies to the InvoiceService.
 */
class InvoiceServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(ServiceInvoice::class);

        $taggedRenderer = $container->findTaggedServiceIds(Kernel::TAG_INVOICE_RENDERER);
        foreach ($taggedRenderer as $id => $tags) {
            $definition->addMethodCall('addRenderer', [new Reference($id)]);
        }

        $taggedGenerator = $container->findTaggedServiceIds(Kernel::TAG_INVOICE_NUMBER_GENERATOR);
        foreach ($taggedGenerator as $id => $tags) {
            $definition->addMethodCall('addNumberGenerator', [new Reference($id)]);
        }

        $taggedCalculator = $container->findTaggedServiceIds(Kernel::TAG_INVOICE_CALCULATOR);
        foreach ($taggedCalculator as $id => $tags) {
            $definition->addMethodCall('addCalculator', [new Reference($id)]);
        }

        $taggedRepository = $container->findTaggedServiceIds(Kernel::TAG_INVOICE_REPOSITORY);
        foreach ($taggedRepository as $id => $tags) {
            $definition->addMethodCall('addInvoiceItemRepository', [new Reference($id)]);
        }
    }
}
