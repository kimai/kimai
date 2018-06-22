<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Dynamically loads additional doctrine functions for the configured database engine.
 */
class DoctrineCompilerPass implements CompilerPassInterface
{
    /**
     * @var string[]
     */
    protected $allowedEngines = [
        'mysql',
        'oracle',
        'postgres',
        'sqlite'
    ];

    /**
     * @param ContainerBuilder $container
     * @return array|false|null|string
     * @throws \Exception
     */
    protected function findEngine(ContainerBuilder $container)
    {
        $engine = null;

        // TODO - this does return the wrong connection. it used to be mysql, even if
        // TODO - getenv('DATABASE_URL') returned an sqlite:// connection string
        /*
        $dbConfig = $container->getExtensionConfig('doctrine');
        if (isset($dbConfig[0]['dbal']['driver'])) {
            $engine = str_replace('pdo_', '', $dbConfig[0]['dbal']['driver']);
        }
        */

        if (null === $engine) {
            $dbConfig = explode('://', getenv('DATABASE_URL'));
            $engine = $dbConfig['0'] ?: null;
        }

        if (null === $engine) {
            $engine = getenv('DATABASE_ENGINE');
        }

        if (null === $engine) {
            throw new \Exception(
                'Could not detect database engine. Please set the environment config DATABASE_ENGINE ' .
                'to one of: "' . implode(', ', $this->allowedEngines) . '" in your .env file: DATABASE_ENGINE=sqlite'
            );
        }

        if (!in_array($engine, $this->allowedEngines)) {
            throw new \Exception(
                'Unsupported database engine: ' . $engine . '. Kimai only supports one of: ' .
                implode(', ', $this->allowedEngines)
            );
        }

        return $engine;
    }

    /**
     * @param ContainerBuilder $container
     * @return string
     * @throws \Exception
     */
    protected function getConfigFile(ContainerBuilder $container)
    {
        $engine = $this->findEngine($container);

        $configDir = realpath(
            $container->getParameter('kernel.project_dir') . '/vendor/beberlei/DoctrineExtensions/config/'
        );

        $configFile = $configDir . '/' . $engine . '.yml';

        if (!file_exists($configFile)) {
            throw new \Exception('Could not find config file for database engine. Looked at ' . $configFile);
        }

        return $configFile;
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $configFile = $this->getConfigFile($container);
        $config = Yaml::parse(file_get_contents($configFile));

        if (!isset($config['doctrine']['orm']['dql']) || empty($config['doctrine']['orm']['dql'])) {
            throw new \Exception('could not load custom Doctrine functions from: ' . $configFile);
        }

        $sql = $config['doctrine']['orm']['dql'];

        $ormConfig = $container->getDefinition('doctrine.orm.default_configuration');

        foreach ($sql['string_functions'] as $name => $function) {
            $ormConfig->addMethodCall('addCustomStringFunction', [$name, $function]);
        }
        foreach ($sql['numeric_functions'] as $name => $function) {
            $ormConfig->addMethodCall('addCustomNumericFunction', [$name, $function]);
        }
        foreach ($sql['datetime_functions'] as $name => $function) {
            $ormConfig->addMethodCall('addCustomDatetimeFunction', [$name, $function]);
        }
    }
}
