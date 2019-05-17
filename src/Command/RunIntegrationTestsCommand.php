<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

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
     * @return string
     */
    protected function createPhpunitCmdLine()
    {
        return '/vendor/bin/phpunit --group integration ' . $this->rootDir . '/tests';
    }
}
