<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\TwigContextCompilerPass;
use App\Export\ServiceExport;
use App\Twig\Configuration;
use App\Twig\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \App\DependencyInjection\Compiler\TwigContextCompilerPass
 */
class TwigContextCompilerPassTest extends TestCase
{
    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kimai.invoice.documents', []); // TODO we could test that as well
        $container->setParameter('kimai.export.documents', []); // TODO we could test that as well

        $definition = new Definition('twig');
        $container->setDefinition('twig', $definition);

        $definition = new Definition('twig.loader.native_filesystem');
        $container->setDefinition('twig.loader.native_filesystem', $definition);

        $definition = new Definition(ServiceExport::class);
        $container->setDefinition(ServiceExport::class, $definition);

        $definition = new Definition(Context::class);
        $container->setDefinition(Context::class, $definition);

        $definition = new Definition(Configuration::class);
        $container->setDefinition(Configuration::class, $definition);

        return $container;
    }

    public function testCallsAreAdded(): void
    {
        $container = $this->getContainer();
        $sut = new TwigContextCompilerPass();
        $sut->process($container);

        $definition = $container->findDefinition('twig');
        $methods = $definition->getMethodCalls();

        self::assertCount(2, $methods);
        self::assertTrue($definition->hasMethodCall('addGlobal'));
        self::assertEquals('addGlobal', $methods[0][0]);
        self::assertEquals('kimai_context', $methods[0][1][0]);
        self::assertEquals('addGlobal', $methods[1][0]);
        self::assertEquals('kimai_config', $methods[1][1][0]);
    }
}
