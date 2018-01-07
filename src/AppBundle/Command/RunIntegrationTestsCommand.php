<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to run all integration tests.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class RunIntegrationTestsCommand extends RunUnitTestsCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('kimai:dev:test-integration')
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
        return $this->rootDir . '/bin/phpunit --group integration ' . $directory;
    }
}
