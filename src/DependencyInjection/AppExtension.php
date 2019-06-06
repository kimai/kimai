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
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This class that loads and manages the Kimai configuration and container parameter.
 */
class AppExtension extends Extension
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
            throw $e;
        }

        // @deprecated since 0.9, duration_only will be removed with 1.0
        if (isset($config['timesheet']['duration_only'])) {
            @trigger_error('Configuration "kimai.timesheet.duration_only" is deprecated, please remove it', E_USER_DEPRECATED);
            if (true === $config['timesheet']['duration_only'] && 'duration_only' !== $config['timesheet']['mode']) {
                trigger_error('Found ambiguous configuration. Please remove "kimai.timesheet.duration_only" and set "kimai.timesheet.mode" instead.');
            }
        }

        // safe alternatives to %kernel.project_dir%
        $container->setParameter('kimai.data_dir', $config['data_dir']);
        $container->setParameter('kimai.plugin_dir', $config['plugin_dir']);

        $container->setParameter('kimai.languages', $config['languages']);
        $container->setParameter('kimai.calendar', $config['calendar']);
        $container->setParameter('kimai.dashboard', $config['dashboard']);
        $container->setParameter('kimai.widgets', $config['widgets']);
        $container->setParameter('kimai.invoice.documents', $config['invoice']['documents']);
        $container->setParameter('kimai.defaults', $config['defaults']);

        $this->createPermissionParameter($config['permissions'], $container);
        $this->createThemeParameter($config['theme'], $container);
        $this->createUserParameter($config['user'], $container);

        $container->setParameter('kimai.config', $config);

        $container->setParameter('kimai.timesheet', $config['timesheet']);
        $container->setParameter('kimai.timesheet.rates', $config['timesheet']['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['timesheet']['rounding']);

        $this->setLdapParameter($config['ldap'], $container);
    }

    protected function setLdapParameter(array $config, ContainerBuilder $container)
    {
        if (!isset($config['connection']['baseDn'])) {
            $config['connection']['baseDn'] = $config['user']['baseDn'];
        }

        if (!isset($config['connection']['accountFilterFormat']) || empty($config['connection']['accountFilterFormat'])) {
            $config['connection']['accountFilterFormat'] = '(&(' . $config['user']['usernameAttribute'] . '=%s))';
            if (!empty($config['user']['filter'])) {
                $config['connection']['accountFilterFormat'] = '(&' . $config['user']['filter'] . '(' . $config['user']['usernameAttribute'] . '=%s))';
            }
        }

        $container->setParameter('kimai.ldap', $config);
    }

    /**
     * Performs some pre-compilation on the configured permissions from kimai.yaml
     * to save us from constant array lookups from during runtime.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function createPermissionParameter(array $config, ContainerBuilder $container)
    {
        $roles = [];
        foreach ($config['maps'] as $role => $sets) {
            foreach ($sets as $set) {
                if (!isset($config['sets'][$set])) {
                    $exception = new InvalidConfigurationException(
                        'Configured permission set "' . $set . '" for role "' . $role . '" is unknown'
                    );
                    $exception->setPath('kimai.permissions.maps.' . $role);
                    throw $exception;
                }
                $roles[$role] = array_merge($roles[$role] ?? [], $this->getFilteredPermissions(
                    $this->extractSinglePermissionsFromSet($config, $set)
                ));
            }
        }

        // delete forbidden permissions from roles
        foreach (array_keys($config['maps']) as $name) {
            $config['roles'][$name] = $this->getFilteredPermissions(
                array_unique(array_merge($roles[$name], $config['roles'][$name] ?? []))
            );
        }

        $container->setParameter('kimai.permissions', $config['roles']);
    }

    protected function getFilteredPermissions(array $permissions): array
    {
        $deleteFromArray = array_filter($permissions, function ($permission) {
            return $permission[0] == '!';
        });

        return array_filter($permissions, function ($permission) use ($deleteFromArray) {
            if ($permission[0] == '!') {
                return false;
            }

            return !in_array('!' . $permission, $deleteFromArray);
        });
    }

    protected function extractSinglePermissionsFromSet(array $permissions, string $name): array
    {
        if (!isset($permissions['sets'][$name])) {
            throw new InvalidConfigurationException('Unknown permission set "' . $name . '"');
        }

        $result = [];

        foreach ($permissions['sets'][$name] as $permissionName) {
            if ($permissionName[0] == '@') {
                $result = array_merge(
                    $result,
                    $this->extractSinglePermissionsFromSet($permissions, substr($permissionName, 1))
                );
            } else {
                $result[] = $permissionName;
            }
        }

        return $result;
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function createThemeParameter(array $config, ContainerBuilder $container)
    {
        $container->setParameter('kimai.theme', $config);
        $container->setParameter('kimai.theme.select_type', $config['select_type']);
        $container->setParameter('kimai.theme.show_about', $config['show_about']);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function createUserParameter(array $config, ContainerBuilder $container)
    {
        if (!$config['registration']) {
            $routes = $container->getParameter('admin_lte_theme.routes');
            $routes['adminlte_registration'] = null;
            $container->setParameter('admin_lte_theme.routes', $routes);
        }

        if (!$config['password_reset']) {
            $routes = $container->getParameter('admin_lte_theme.routes');
            $routes['adminlte_password_reset'] = null;
            $container->setParameter('admin_lte_theme.routes', $routes);
        }

        $container->setParameter('kimai.fosuser', $config);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'kimai';
    }
}
