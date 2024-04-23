<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\DependencyInjection\AppExtension;
use App\DependencyInjection\Compiler\ExportServiceCompilerPass;
use App\DependencyInjection\Compiler\InvoiceServiceCompilerPass;
use App\DependencyInjection\Compiler\TwigContextCompilerPass;
use App\DependencyInjection\Compiler\WidgetCompilerPass;
use App\Ldap\FormLoginLdapFactory;
use App\Plugin\PluginInterface;
use App\Plugin\PluginMetadata;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const PLUGIN_DIRECTORY = '/var/plugins';
    public const CONFIG_EXTS = '.{php,yaml}';

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    protected function build(ContainerBuilder $container): void
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addAuthenticatorFactory(new FormLoginLdapFactory());
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }

        if ($this->environment === 'test' && getenv('TEST_WITH_BUNDLES') === false) {
            return;
        }

        // we can either define all kimai bundles hardcoded ...
        if (is_file($this->getProjectDir() . '/config/bundles-local.php')) {
            $contents = require $this->getProjectDir() . '/config/bundles-local.php';
            foreach ($contents as $class => $envs) {
                if (isset($envs['all']) || isset($envs[$this->environment])) {
                    yield new $class();
                }
            }
        } else {
            // ... or we load them dynamically from the plugins directory
            foreach ($this->getBundleClasses() as $plugin) {
                yield $plugin;
            }
        }
    }

    private function getBundleClasses(): array
    {
        $pluginsDir = $this->getProjectDir() . self::PLUGIN_DIRECTORY;
        if (!file_exists($pluginsDir)) {
            return [];
        }

        $plugins = [];
        $finder = new Finder();
        $finder->ignoreUnreadableDirs()->directories()->name('*Bundle');
        /** @var SplFileInfo $bundleDir */
        foreach ($finder->in($pluginsDir) as $bundleDir) {
            $bundleName = $bundleDir->getRelativePathname();
            $fullPath = $bundleDir->getRealPath();

            if (file_exists($fullPath . '/.disabled')) {
                continue;
            }

            $pluginClass = 'KimaiPlugin\\' . $bundleName . '\\' . $bundleName;
            if (!class_exists($pluginClass)) {
                continue;
            }

            $plugin = new $pluginClass();
            if (!$plugin instanceof PluginInterface) {
                throw new \Exception(sprintf('Bundle "%s" does not implement %s, which is not supported since 2.0.', $bundleName, PluginInterface::class));
            }

            $meta = new PluginMetadata($fullPath);

            if ($meta->getKimaiVersion() > Constants::VERSION_ID) {
                throw new \Exception(sprintf('Bundle "%s" requires minimum Kimai version %s, but yours is lower: %s (%s). Please update Kimai or use a lower Plugin version.', $bundleName, $meta->getKimaiVersion(), Constants::VERSION, Constants::VERSION_ID));
            }

            $plugins[] = $plugin;
        }

        return $plugins;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->registerExtension(new AppExtension());

        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('.container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';

        // using this one instead of $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        // to get rid of the local.yaml from the list: we load it afterward explicit
        $finder = (new Finder())
            ->files()
            ->in([$confDir . '/packages/'])
            ->name('*' . self::CONFIG_EXTS)
            ->notName('local.yaml')
            ->sortByName()
            ->followLinks()
        ;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $loader->load($file->getPathname());
        }

        if (is_file($confDir . '/packages/local.yaml')) {
            $loader->load($confDir . '/packages/local.yaml');
        }
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');

        $container->addCompilerPass(new TwigContextCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new InvoiceServiceCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new ExportServiceCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new WidgetCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
    }

    private function configureRoutes(RoutingConfigurator $routes): void // @phpstan-ignore-line
    {
        $configDir = $this->getConfigDir();

        // load application specific route files
        $routes->import($configDir . '/routes/*.yaml');

        // load environment specific route files if available
        if (is_dir($configDir . '/routes/' . $this->environment)) {
            $routes->import($configDir . '/routes/' . $this->environment . '/*.yaml');
        }

        // load application routes
        $routes->import($configDir . '/routes.yaml');

        foreach ($this->getBundles() as $bundle) {
            if (str_contains(\get_class($bundle), 'KimaiPlugin\\')) {
                if (is_dir($bundle->getPath() . '/Resources/config/')) {
                    $routes->import($bundle->getPath() . '/Resources/config/routes' . self::CONFIG_EXTS);
                } elseif (is_dir($bundle->getPath() . '/config/')) {
                    $routes->import($bundle->getPath() . '/config/routes' . self::CONFIG_EXTS);
                }
            }
        }
    }
}
