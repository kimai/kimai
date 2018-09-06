<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Command used to run all integration tests.
 */
class RunIntegrationTestsCommand extends RunUnitTestsCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:test-integration')
            ->setDescription('Run all integration tests')
            ->setHelp('This command will execute all integration tests with the annotation "@group integration".')
        ;
    }

    /**
     * @param $directory
     * @return string
     */
    protected function createPhpunitCmdLine($directory)
    {
        return 'SYMFONY_DEPRECATIONS_HELPER=weak ' . $this->rootDir . '/bin/phpunit --group integration ' . $directory;
    }
}
