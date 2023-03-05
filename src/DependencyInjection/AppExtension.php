<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use App\Kernel;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Intl\Locales;

/**
 * This class that loads and manages the Kimai configuration and container parameter.
 */
final class AppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        try {
            $config = $this->processConfiguration($configuration, $configs);
        } catch (InvalidConfigurationException $e) {
            trigger_error('Found invalid "kimai" configuration: ' . $e->getMessage());
            throw $e;
        }

        // we use a comma separated string internally, to be able to use it in combination with the database configuration system
        foreach ($config['timesheet']['rounding'] as $name => $settings) {
            $config['timesheet']['rounding'][$name]['days'] = implode(',', $settings['days']);
        }

        $config['invoice']['documents'] = array_merge($config['invoice']['documents'], $config['invoice']['defaults']);
        unset($config['invoice']['defaults']);

        $config['export']['documents'] = array_merge($config['export']['documents'], $config['export']['defaults']);
        unset($config['export']['defaults']);

        if (empty($config['data_dir'])) {
            $config['data_dir'] = $container->getParameter('kernel.project_dir') . '/var/data';
        }
        $container->setParameter('kimai.data_dir', $config['data_dir']);
        $container->setParameter('kimai.plugin_dir', $container->getParameter('kernel.project_dir') . Kernel::PLUGIN_DIRECTORY);

        $this->setLanguageFormats($container);

        $container->setParameter('kimai.invoice.documents', $config['invoice']['documents']);
        $container->setParameter('kimai.export.documents', $config['export']['documents']);

        $this->createPermissionParameter($config['permissions'], $container);
        $container->setParameter('kimai.timesheet.rates', $config['timesheet']['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['timesheet']['rounding']);

        if (!isset($config['ldap']['connection']['baseDn'])) {
            $config['ldap']['connection']['baseDn'] = $config['ldap']['user']['baseDn'];
        }

        if (empty($config['ldap']['connection']['accountFilterFormat']) && $config['ldap']['connection']['bindRequiresDn']) {
            $filter = '';
            if (!empty($config['ldap']['user']['filter'])) {
                $filter = $config['ldap']['user']['filter'];
            }
            $config['ldap']['connection']['accountFilterFormat'] = '(&' . $filter . '(' . $config['ldap']['user']['usernameAttribute'] . '=%s))';
        }

        // this should happen always at the end, so bundles do not mess with the base configuration
        if ($container->hasParameter('kimai.bundles.config')) {
            $bundleConfig = $container->getParameter('kimai.bundles.config');
            if (!\is_array($bundleConfig)) {
                throw new \Exception('Invalid bundle configuration found, skipping all bundle configuration');
            }
            foreach ($bundleConfig as $key => $value) {
                if (\array_key_exists($key, $config)) {
                    throw new \Exception(sprintf('Invalid bundle configuration "%s" found, skipping', $key));
                }
                $config[$key] = $value;
            }
        }

        // cleanup for caching
        unset($config['invoice']['documents']);
        unset($config['export']);
        unset($config['dashboard']);
        unset($config['data_dir']);
        unset($config['permissions']);

        // make configs a flat dotted notation during compile time, this will save us from the need to
        // parse each and every call to the config, but allows direct access
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($config, \RecursiveArrayIterator::CHILD_ARRAYS_ONLY));
        $newConfig = [];
        foreach ($iterator as $value) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $newConfig[implode('.', $keys)] = $value;
        }

        $container->setParameter('kimai.config', $newConfig);
    }

    private function setLanguageFormats(ContainerBuilder $container): void
    {
        $locales = explode('|', $container->getParameter('app_locales'));

        $directory = $container->getParameter('kernel.project_dir');
        $config = $directory . DIRECTORY_SEPARATOR . 'config/locales.php';
        $settings = include $config;

        $appLocales = [];
        $defaults = [
            'date' => 'dd.MM.y',
            'time' => 'HH:mm',
            'rtl' => false,
        ];

        // make sure all allowed locales are registered
        foreach ($locales as $locale) {
            // unlikely that a locale disappears, but in case that a new symfony update comes with changed locales
            if (!Locales::exists($locale)) {
                continue;
            }

            $appLocales[$locale] = $defaults;

            if (\array_key_exists($locale, $settings)) {
                $appLocales[$locale] = array_merge($appLocales[$locale], $settings[$locale]);
            }
        }

        ksort($appLocales);

        $container->setParameter('kimai.languages', $appLocales);
    }

    /**
     * Performs some pre-compilation on the configured permissions from kimai.yaml
     * to save us from constant array lookups from during runtime.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createPermissionParameter(array $config, ContainerBuilder $container): void
    {
        $names = [];
        // this does not include all possible permission, as plugins do not register them and Kimai defines a couple of
        // permissions as well, which are off by default for all roles
        foreach ($config['sets'] as $set => $permNames) {
            foreach ($permNames as $name) {
                if (str_starts_with($name, '@') || str_starts_with($name, '!')) {
                    continue;
                }
                $names[$name] = true;
            }
        }

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
            if (\array_key_exists($name, $config['roles'])) {
                foreach ($config['roles'][$name] as $name2) {
                    $roles[$name][$name2] = true;
                }
            }
            $config['roles'][$name] = $this->getFilteredPermissions($roles[$name]);
        }

        // make sure to apply all other permissions that might have been registered through plugins
        foreach ($config['roles'] as $role => $perms) {
            $names = array_merge($names, $perms);
        }

        /** @var array<string, array<string>> $roles */
        $securityRoles = $container->getParameter('security.role_hierarchy.roles');
        $roles = [];
        foreach ($securityRoles as $key => $value) {
            $roles[] = $key;
            foreach ($value as $name) {
                $roles[] = $name;
            }
        }

        $container->setParameter('kimai.permissions', $config['roles']);
        $container->setParameter('kimai.permission_names', $names);
        $container->setParameter('kimai.permission_roles', array_map('strtoupper', array_values(array_unique($roles))));
    }

    private function getFilteredPermissions(array $permissions): array
    {
        $deleteFromArray = array_filter($permissions, function ($permission): bool {
            return $permission[0] === '!';
        }, ARRAY_FILTER_USE_KEY);

        return array_filter($permissions, function ($permission) use ($deleteFromArray): bool {
            if ($permission[0] === '!') {
                return false;
            }

            return !\array_key_exists('!' . $permission, $deleteFromArray);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function extractSinglePermissionsFromSet(array $permissions, string $name): array
    {
        if (!isset($permissions['sets'][$name])) {
            throw new InvalidConfigurationException('Unknown permission set "' . $name . '"');
        }

        $result = [];

        foreach ($permissions['sets'][$name] as $permissionName) {
            if ($permissionName[0] === '@') {
                $result = array_merge(
                    $result,
                    $this->extractSinglePermissionsFromSet($permissions, substr($permissionName, 1))
                );
            } else {
                $result[$permissionName] = true;
            }
        }

        return $result;
    }

    public function getAlias(): string
    {
        return 'kimai';
    }
}
