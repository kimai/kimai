<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\DependencyInjection\AppExtension;
use App\DependencyInjection\Compiler\DoctrineCompilerPass;
use App\Timesheet\CalculatorInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir()
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/var/log';
    }

    protected function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(CalculatorInterface::class)->addTag('timesheet.calculator');
    }

    public function registerBundles()
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->registerExtension(new AppExtension());

        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';
        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir . '/packages/local' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');

        $container->addCompilerPass(new DoctrineCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $confDir = $this->getProjectDir() . '/config';

        // some routes are based on app configs and will be imported manually
        $this->configureFosUserRoutes($routes);

        // load bundle specific route files
        if (is_dir($confDir . '/routes/')) {
            $routes->import($confDir . '/routes/*' . self::CONFIG_EXTS, '/', 'glob');
        }

        // load environment specific route files
        if (is_dir($confDir . '/routes/' . $this->environment)) {
            $routes->import($confDir . '/routes/' . $this->environment . '/**/*' . self::CONFIG_EXTS, '/', 'glob');
        }

        // load application routes
        $routes->import($confDir . '/routes' . self::CONFIG_EXTS, '/', 'glob');
    }

    protected function configureFosUserRoutes(RouteCollectionBuilder $routes)
    {
        $features = $this->getContainer()->getParameter('kimai.fosuser');

        // Expose the user registration feature
        if ($features['registration']) {
            $routes->import(
                '@FOSUserBundle/Resources/config/routing/registration.xml',
                '/{_locale}/register'
            );
        }

        // Expose the users password-reset feature
        if ($features['password_reset']) {
            $routes->import(
                '@FOSUserBundle/Resources/config/routing/resetting.xml',
                '/{_locale}/resetting'
            );
        }
    }
}
