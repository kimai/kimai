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
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class AppExtension extends Extension implements PrependExtensionInterface
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

        $this->createTimesheetParameter($config, $container);
        $this->createInvoiceParameter($config, $container);
    }

    public function createTimesheetParameter(array $config, ContainerBuilder $container)
    {
        $container->setParameter('kimai.timesheet.rates', $config['timesheet']['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['timesheet']['rounding']);
        $container->setParameter('kimai.timesheet.duration_only', $config['timesheet']['duration_only']);
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
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $configs = $container->getExtensionConfig($this->getAlias());
        try {
            $config = $this->processConfiguration($configuration, $configs);
        } catch (InvalidConfigurationException $e) {
            trigger_error('Found invalid "kimai" configuration: ' . $e->getMessage());
            $config = [];
        }

        $container->prependExtensionConfig(
            'twig',
            [
                'globals' => [
                    'duration_only' => $config['timesheet']['duration_only'],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'kimai';
    }
}
