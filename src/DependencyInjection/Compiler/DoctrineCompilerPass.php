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
    private $allowedEngines = [
        'mysql',
        'sqlite'
    ];

    private function getEnvVar(string $name): ?string
    {
        $envVarValue = null;

        if (isset($_ENV[$name])) {
            $envVarValue = $_ENV[$name];
        }

        if ($envVarValue === null && isset($_SERVER[$name])) {
            $envVarValue = $_SERVER[$name];
        }

        if ($envVarValue === null) {
            $envVarValue = getenv($name);
        }

        if ($envVarValue === false || empty($envVarValue)) {
            return null;
        }

        return $envVarValue;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function findEngine(): string
    {
        $engine = null;

        if (null !== ($databaseUrl = $this->getEnvVar('DATABASE_URL'))) {
            $urlParts = explode('://', $databaseUrl);
            $engine = $urlParts[0] ?: null;
        }

        if ($engine === null) {
            $engine = $this->getEnvVar('DATABASE_ENGINE');
        }

        if ($engine === null) {
            throw new \Exception(
                'Could not detect database engine, make sure DATABASE_URL is available from $_SERVER or $_ENV. Check your .env file.'
            );
        }

        if (!\in_array($engine, $this->allowedEngines)) {
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
        $engine = $this->findEngine();

        $configDir = realpath(
            $container->getParameter('kernel.project_dir') . '/config/packages/doctrine/'
        );

        $configFile = $configDir . '/' . $engine . '.yaml';

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
