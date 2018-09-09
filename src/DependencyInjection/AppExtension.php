<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This class that loads and manages the Kimai configuration and container parameter.
 */
class AppExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
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

        $container->setParameter('kimai.languages', $config['languages']);
        $container->setParameter('kimai.calendar', $config['calendar']);
        $container->setParameter('kimai.theme', $config['theme']);
        $container->setParameter('kimai.dashboard', $config['dashboard']);
        $container->setParameter('kimai.widgets', $config['widgets']);
        $container->setParameter('kimai.invoice.documents', $config['invoice']['documents']);

        $this->createUserParameter($config, $container);
        $this->createTimesheetParameter($config, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function createUserParameter(array $config, ContainerBuilder $container)
    {
        if (!$config['user']['registration']) {
            $routes = $container->getParameter('admin_lte_theme.routes');
            $routes['adminlte_registration'] = null;
            $container->setParameter('admin_lte_theme.routes', $routes);
        }

        if (!$config['user']['password_reset']) {
            $routes = $container->getParameter('admin_lte_theme.routes');
            $routes['adminlte_password_reset'] = null;
            $container->setParameter('admin_lte_theme.routes', $routes);
        }

        $container->setParameter('kimai.fosuser', $config['user']);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function createTimesheetParameter(array $config, ContainerBuilder $container)
    {
        $container->setParameter('kimai.timesheet.rates', $config['timesheet']['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['timesheet']['rounding']);
        $container->setParameter('kimai.timesheet.duration_only', $config['timesheet']['duration_only']);
        $container->setParameter('kimai.timesheet.markdown', $config['timesheet']['markdown_content']);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        /*
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
        */
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'kimai';
    }
}
