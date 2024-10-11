<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\InvoiceServiceCompilerPass;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\Calculator\ShortInvoiceCalculator;
use App\Invoice\Calculator\UserInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Invoice\NumberGenerator\ConfigurableNumberGenerator;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\NumberGeneratorInterface;
use App\Invoice\Renderer\DocxRenderer;
use App\Invoice\RendererInterface;
use App\Invoice\ServiceInvoice;
use App\Repository\TimesheetInvoiceItemRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \App\DependencyInjection\Compiler\InvoiceServiceCompilerPass
 */
class InvoiceServiceCompilerPassTest extends TestCase
{
    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $definition = new Definition(ServiceInvoice::class);
        $container->setDefinition(ServiceInvoice::class, $definition);

        $renderers = [DocxRenderer::class];
        foreach ($renderers as $renderer) {
            $container->register($renderer)->addTag(RendererInterface::class);
        }

        $numberGenerators = [DateNumberGenerator::class, ConfigurableNumberGenerator::class];
        foreach ($numberGenerators as $numberGenerator) {
            $container->register($numberGenerator)->addTag(NumberGeneratorInterface::class);
        }

        $calculators = [DefaultCalculator::class, UserInvoiceCalculator::class, ShortInvoiceCalculator::class];
        foreach ($calculators as $calculator) {
            $container->register($calculator)->addTag(CalculatorInterface::class);
        }

        $repositories = [TimesheetInvoiceItemRepository::class];
        foreach ($repositories as $repository) {
            $container->register($repository)->addTag(InvoiceItemRepositoryInterface::class);
        }

        return $container;
    }

    public function testCallsAreAdded(): void
    {
        $container = $this->getContainer();
        $sut = new InvoiceServiceCompilerPass();
        $sut->process($container);

        $definition = $container->findDefinition(ServiceInvoice::class);
        $methods = $definition->getMethodCalls();

        self::assertCount(7, $methods);
        self::assertTrue($definition->hasMethodCall('addRenderer'));
        self::assertTrue($definition->hasMethodCall('addNumberGenerator'));
        self::assertTrue($definition->hasMethodCall('addCalculator'));
        self::assertTrue($definition->hasMethodCall('addInvoiceItemRepository'));
    }
}
