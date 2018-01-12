<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use \Doctrine\Common\ClassLoader;

/**
 * Main extension class
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class KimaiExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $rootDir = realpath($container->getParameter('kernel.root_dir'));
        $extensionsDir = realpath($rootDir . '/../vendor/beberlei/DoctrineExtensions/src/');

        $classLoader = new ClassLoader('DoctrineExtensions', $extensionsDir);
        $classLoader->register();

        $kimaiConfig = new Configuration();
        $this->processConfiguration($kimaiConfig, $configs);
    }
}
