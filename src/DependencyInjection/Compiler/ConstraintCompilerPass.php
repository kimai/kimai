<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Validator\Constraints\ProjectConstraint;
use App\Validator\Constraints\TimesheetConstraint;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConstraintCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $constraints = [
            TimesheetConstraint::class,
            ProjectConstraint::class,
        ];

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }

            if (!str_starts_with($class, 'Symfony\\') && str_contains($class, '\\Constraints\\')) {
                $reflectionClass = $container->getReflectionClass($class, false);
                if ($reflectionClass === null) {
                    continue;
                }

                $parent = $reflectionClass->getParentClass();
                if ($parent === false) {
                    continue;
                }

                $parent = $parent->getName();
                if (\in_array($parent, $constraints, true)) {
                    $definition->clearTag('container.excluded');
                }
            }
        }
    }
}
