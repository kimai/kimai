<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\WidgetCompilerPass;
use App\Widget\Type\ActiveTimesheets;
use App\Widget\Type\ActiveUsersMonth;
use App\Widget\WidgetInterface;
use App\Widget\WidgetService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \App\DependencyInjection\Compiler\WidgetCompilerPass
 */
class WidgetCompilerPassTest extends TestCase
{
    private function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $definition = new Definition(WidgetService::class);
        $container->setDefinition(WidgetService::class, $definition);

        $widgets = [ActiveTimesheets::class, ActiveUsersMonth::class];
        foreach ($widgets as $widget) {
            $container->register($widget)->addTag(WidgetInterface::class);
        }

        return $container;
    }

    public function testCallsAreAdded(): void
    {
        $container = $this->getContainer();
        $sut = new WidgetCompilerPass();
        $sut->process($container);

        $definition = $container->findDefinition(WidgetService::class);
        $methods = $definition->getMethodCalls();

        self::assertCount(2, $methods);
        self::assertTrue($definition->hasMethodCall('registerWidget'));
    }
}
