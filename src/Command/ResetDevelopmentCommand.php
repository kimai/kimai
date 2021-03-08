<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * This command is NOT used during runtime and only meant for developers on their local machines.
 * This is one of the cases where I don't feel like it is necessary to add tests, so lets "cheat" with:
 * @codeCoverageIgnore
 */
class ResetDevelopmentCommand extends AbstractResetCommand
{
    protected function getEnvName(): string
    {
        return 'dev';
    }

    protected function loadData(InputInterface $input, OutputInterface $output): void
    {
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $cmdInput = new ArrayInput([]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);
    }
}
