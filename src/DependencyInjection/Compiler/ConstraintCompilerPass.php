<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConstraintCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $classes = [];
        foreach($container->findTaggedResourceIds('validator.timesheet') as $id => $tags) {
            $classes[] = $container->getDefinition($id)->getClass();
        }

        $container->setParameter('kimai.validator_timesheet', $classes);
    }
}
