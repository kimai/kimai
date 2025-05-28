<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpSubprocess;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * This command is NOT used during runtime and only meant for developers on their local machines.
 * This is one of the cases where I don't feel like it is necessary to add tests, so lets "cheat" with:
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:reset:dev', description: 'Resets the "development" environment')]
final class ResetDevelopmentCommand extends AbstractResetCommand
{
    public function __construct(string $kernelEnvironment, private readonly string $projectDirectory)
    {
        parent::__construct($kernelEnvironment);
    }

    protected function loadData(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Importing fixtures, this will take a while ... please be patient ...');

        $process = new PhpSubprocess(
            [
                'bin/console',
                'doctrine:fixtures:load',
                '--no-interaction',
            ],
            $this->projectDirectory
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Failed to load fixtures');
            $io->error($process->getErrorOutput());
        } else {
            if ($io->isVerbose()) {
                $io->write($process->getOutput());
            } else {
                $io->success('Fixtures loaded');
            }
        }
    }
}
