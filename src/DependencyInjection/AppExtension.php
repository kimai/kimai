<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection;

use App\Constants;
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

        // @deprecated since 0.9, duration_only will be removed with 2.0
        if (isset($config['timesheet']['duration_only'])) {
            @trigger_error('Configuration "kimai.timesheet.duration_only" is deprecated, please remove it', E_USER_DEPRECATED);
            if (true === $config['timesheet']['duration_only'] && 'duration_only' !== $config['timesheet']['mode']) {
                trigger_error('Found ambiguous configuration: remove "kimai.timesheet.duration_only" and set "kimai.timesheet.mode" instead.');
            }
        }

        // we use a comma sepearated string internally, to be able to use it in combination with the database configuration system
        foreach ($config['timesheet']['rounding'] as $name => $settings) {
            $config['timesheet']['rounding'][$name]['days'] = implode(',', $settings['days']);
        }

        $config['invoice']['documents'] = array_merge($config['invoice']['documents'], $config['invoice']['defaults']);
        unset($config['invoice']['defaults']);

        $config['export']['documents'] = array_merge($config['export']['documents'], $config['export']['defaults']);
        unset($config['export']['defaults']);

        // safe alternatives to %kernel.project_dir%
        $container->setParameter('kimai.data_dir', $config['data_dir']);
        $container->setParameter('kimai.plugin_dir', $config['plugin_dir']);

        $this->setLanguageFormats($config['languages'], $container);
        unset($config['languages']);

        $container->setParameter('kimai.calendar', $config['calendar']);
        $container->setParameter('kimai.dashboard', $config['dashboard']);
        $container->setParameter('kimai.widgets', $config['widgets']);
        $container->setParameter('kimai.invoice.documents', $config['invoice']['documents']);
        $container->setParameter('kimai.export.documents', $config['export']['documents']);
        $container->setParameter('kimai.defaults', $config['defaults']);

        $this->createPermissionParameter($config['permissions'], $container);
        $this->createThemeParameter($config['theme'], $container);
        $this->createUserParameter($config['user'], $container);
        $container->setParameter('kimai.saml', $config['saml']);
        $container->setParameter('kimai.saml.connection', $config['saml']['connection']);
        $container->setParameter('kimai.timesheet', $config['timesheet']);
        $container->setParameter('kimai.timesheet.rates', $config['timesheet']['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['timesheet']['rounding']);

        $this->setLdapParameter($config['ldap'], $container);

        // translation files, which can overwrite the default kimai translations
        $localTranslations = [];
        if (null !== $config['theme']['branding']['translation']) {
            $localTranslations[] = $config['theme']['branding']['translation'];
        }
        if (null !== $config['industry']['translation']) {
            $localTranslations[] = $config['industry']['translation'];
        }
        $container->setParameter('kimai.i18n_domains', $localTranslations);

        // this should happen always at the end, so bundles do not mess with the base configuration
        if ($container->hasParameter('kimai.bundles.config')) {
            $bundleConfig = $container->getParameter('kimai.bundles.config');
            if (!\is_array($bundleConfig)) {
                trigger_error('Invalid bundle configuration found, skipping all bundle configuration');
            }
            foreach ($bundleConfig as $key => $value) {
                if (\array_key_exists($key, $config)) {
                    trigger_error(sprintf('Invalid bundle configuration "%s" found, skipping', $key));
                    continue;
                }
                $config[$key] = $value;
            }
        }
        $container->setParameter('kimai.config', $config);
    }

    protected function setLanguageFormats(array $config, ContainerBuilder $container)
    {
        $locales = explode('|', $container->getParameter('app_locales'));

        // make sure all allowed locales are registered
        foreach ($locales as $locale) {
            if (!\array_key_exists($locale, $config)) {
                $config[$locale] = $config[Constants::DEFAULT_LOCALE];
            }
        }

        // make sure all keys are registered for every locale
        foreach ($config as $locale => $settings) {
            if ($locale === Constants::DEFAULT_LOCALE) {
                continue;
            }
            // pre-fill all formats with the default locale settings
            $config[$locale] = array_merge($config[Constants::DEFAULT_LOCALE], $config[$locale]);
        }

        $container->setParameter('kimai.languages', $config);
    }

    protected function setLdapParameter(array $config, ContainerBuilder $container)
    {
        if (!isset($config['connection']['baseDn'])) {
            $config['connection']['baseDn'] = $config['user']['baseDn'];
        }

        if (empty($config['connection']['accountFilterFormat']) && $config['connection']['bindRequiresDn']) {
            $filter = '';
            if (!empty($config['user']['filter'])) {
                $filter = $config['user']['filter'];
            }
            $config['connection']['accountFilterFormat'] = '(&' . $filter . '(' . $config['user']['usernameAttribute'] . '=%s))';
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

            return !\in_array('!' . $permission, $deleteFromArray);
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
