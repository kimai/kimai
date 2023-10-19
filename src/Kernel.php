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
use App\Export\ExportRepositoryInterface;
use App\Export\RendererInterface as ExportRendererInterface;
use App\Export\TimesheetExportInterface;
use App\Invoice\CalculatorInterface as InvoiceCalculator;
use App\Invoice\InvoiceItemRepositoryInterface;
use App\Invoice\NumberGeneratorInterface;
use App\Invoice\RendererInterface as InvoiceRendererInterface;
use App\Ldap\FormLoginLdapFactory;
use App\Plugin\PluginInterface;
use App\Plugin\PluginMetadata;
use App\Timesheet\CalculatorInterface as TimesheetCalculator;
use App\Timesheet\Rounding\RoundingInterface;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Validator\Constraints\ProjectConstraint;
use App\Validator\Constraints\TimesheetConstraint;
use App\Widget\WidgetInterface;
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

    public const TAG_PLUGIN = 'kimai.plugin';
    public const TAG_WIDGET = 'widget';
    public const TAG_EXPORT_RENDERER = 'export.renderer';
    public const TAG_EXPORT_REPOSITORY = 'export.repository';
    public const TAG_INVOICE_RENDERER = 'invoice.renderer';
    public const TAG_INVOICE_NUMBER_GENERATOR = 'invoice.number_generator';
    public const TAG_INVOICE_CALCULATOR = 'invoice.calculator';
    public const TAG_INVOICE_REPOSITORY = 'invoice.repository';
    public const TAG_TIMESHEET_CALCULATOR = 'timesheet.calculator';
    public const TAG_TIMESHEET_VALIDATOR = 'timesheet.validator';
    public const TAG_TIMESHEET_EXPORTER = 'timesheet.exporter';
    public const TAG_TIMESHEET_TRACKING_MODE = 'timesheet.tracking_mode';
    public const TAG_TIMESHEET_ROUNDING_MODE = 'timesheet.rounding_mode';
    public const TAG_PROJECT_VALIDATOR = 'project.validator';

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
        $container->registerForAutoconfiguration(TimesheetCalculator::class)->addTag(self::TAG_TIMESHEET_CALCULATOR);
        $container->registerForAutoconfiguration(ExportRendererInterface::class)->addTag(self::TAG_EXPORT_RENDERER);
        $container->registerForAutoconfiguration(ExportRepositoryInterface::class)->addTag(self::TAG_EXPORT_REPOSITORY);
        $container->registerForAutoconfiguration(InvoiceRendererInterface::class)->addTag(self::TAG_INVOICE_RENDERER);
        $container->registerForAutoconfiguration(NumberGeneratorInterface::class)->addTag(self::TAG_INVOICE_NUMBER_GENERATOR);
        $container->registerForAutoconfiguration(InvoiceCalculator::class)->addTag(self::TAG_INVOICE_CALCULATOR);
        $container->registerForAutoconfiguration(InvoiceItemRepositoryInterface::class)->addTag(self::TAG_INVOICE_REPOSITORY);
        $container->registerForAutoconfiguration(PluginInterface::class)->addTag(self::TAG_PLUGIN);
        $container->registerForAutoconfiguration(WidgetInterface::class)->addTag(self::TAG_WIDGET);
        $container->registerForAutoconfiguration(TimesheetExportInterface::class)->addTag(self::TAG_TIMESHEET_EXPORTER);
        $container->registerForAutoconfiguration(TrackingModeInterface::class)->addTag(self::TAG_TIMESHEET_TRACKING_MODE);
        $container->registerForAutoconfiguration(RoundingInterface::class)->addTag(self::TAG_TIMESHEET_ROUNDING_MODE);
        $container->registerForAutoconfiguration(TimesheetConstraint::class)->addTag(self::TAG_TIMESHEET_VALIDATOR);
        $container->registerForAutoconfiguration(ProjectConstraint::class)->addTag(self::TAG_PROJECT_VALIDATOR);

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

            $meta = PluginMetadata::loadFromComposer($fullPath);

            if ($meta->getKimaiVersion() > Constants::VERSION_ID) {
                throw new \Exception(sprintf('Bundle "%s" requires minimum Kimai version %s, but yours is lower: %s (%s). Please update Kimai or use a lower Plugin version.', $bundleName, $meta->getKimaiVersion(), Constants::VERSION, Constants::VERSION_ID));
            }

            $plugins[] = $plugin;
        }

        return $plugins;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->registerExtension(new AppExtension());

        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('.container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';

        // using this one instead of $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        // to get rid of the local.yaml from the list, we load it afterwards explicit
        $finder = (new Finder())
            ->files()
            ->in([$confDir . '/packages/'])
            ->name('*' . self::CONFIG_EXTS)
            ->sortByName()
            ->followLinks()
        ;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $loader->load($file->getPathname());
        }

        if (is_file($confDir . '/packages/local.yaml')) {
            $loader->load($confDir . '/packages/local.yaml', 'glob');
        }
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');

        $container->addCompilerPass(new TwigContextCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new InvoiceServiceCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new ExportServiceCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
        $container->addCompilerPass(new WidgetCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
    }

    /** @phpstan-ignore-next-line */
    private function configureRoutes(RoutingConfigurator $routes): void
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
