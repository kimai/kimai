<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Dynamically loads additional doctrine functions for the configured database engine.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class DoctrineCompilerPass implements CompilerPassInterface
{

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $engine = $container->getParameter('database_engine');
        if (null === $engine) {
            throw new ParameterNotFoundException('database_engine');
        }

        $ormConfigDef = $container->getDefinition('doctrine.orm.default_configuration');

        $configDir = realpath($container->getParameter('kernel.root_dir') . '/config/');
        $config = Yaml::parse(file_get_contents($configDir . '/' . $engine . '.yml'));

        if (!isset($config['doctrine']['orm']['dql'])) {
            return;
        }

        $sql = $config['doctrine']['orm']['dql'];

        if (!empty($sql)) {
            foreach ($sql['string_functions'] as $name => $function) {
                $ormConfigDef->addMethodCall('addCustomStringFunction', array($name, $function));
            }
            foreach ($sql['numeric_functions'] as $name => $function) {
                $ormConfigDef->addMethodCall('addCustomNumericFunction', array($name, $function));
            }
            foreach ($sql['datetime_functions'] as $name => $function) {
                $ormConfigDef->addMethodCall('addCustomDatetimeFunction', array($name, $function));
            }
        }
    }
}
