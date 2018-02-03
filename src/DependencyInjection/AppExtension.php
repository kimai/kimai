<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class AppExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        try {
            $config = $this->processConfiguration($configuration, $configs);
        } catch (InvalidConfigurationException $e) {
            trigger_error('Found invalid "kimai" configuration: ' . $e->getMessage());
            $config = [];
        }

        $this->createInvoiceParameter($config, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createInvoiceParameter(array $config, ContainerBuilder $container)
    {
        $keys = ['renderer', 'calculator', 'number_generator'];

        foreach ($keys as $key) {
            if (!isset($config['invoice'][$key]) || 0 === count($config['invoice'][$key])) {
                throw new InvalidDefinitionException('Missing invoice configuration: kimai.invoice.' . $key);
            }

            $container->setParameter('kimai.invoice.' . $key, $config['invoice'][$key]);
        }

        $container->setParameter('kimai.invoice', $config['invoice']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'kimai';
    }
}
