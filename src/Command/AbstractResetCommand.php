<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base class for all re-installation commands, which are not used during application runtime.
 * @codeCoverageIgnore
 */
abstract class AbstractResetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('kimai:reset:' . $this->getEnvName())
            ->setAliases(['kimai:reset-' . $this->getEnvName()])
            ->setDescription('Resets the "' . $this->getEnvName() . '" environment')
            ->setHelp(
                <<<EOT
    This command will drop and re-create the database and its schemas, load data and clear the cache.
    Use the <info>-n</info> switch to skip the question.
EOT
            )
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Skip cache flushing')
        ;
    }

    public function isEnabled(): bool
    {
        return $this->getEnv() !== 'prod';
    }

    private function getEnv(): string
    {
        /** @var Application $application */
        $application = $this->getApplication();
        $kernel = $application->getKernel();

        return $kernel->getEnvironment();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->askConfirmation($input, $output, 'Do you want to create the database y/N ?')) {
            try {
                $command = $this->getApplication()->find('doctrine:database:create');
                $options = ['--if-not-exists' => true];
                $command->run(new ArrayInput($options), $output);
            } catch (Exception $ex) {
                $io->error('Failed to create database: ' . $ex->getMessage());

                return 1;
            }
        }

        if ($this->askConfirmation($input, $output, 'Do you want to drop and re-create the schema y/N ?')) {
            if (($result = $this->dropSchema($io, $output)) !== 0) {
                return $result;
            }

            try {
                $command = $this->getApplication()->find('doctrine:migrations:migrate');
                $cmdInput = new ArrayInput([]);
                $cmdInput->setInteractive(false);
                $command->run($cmdInput, $output);
            } catch (Exception $ex) {
                $io->error('Failed to execute a migrations: ' . $ex->getMessage());

                return 5;
            }
        }

        try {
            $this->loadData($input, $output);
        } catch (Exception $ex) {
            $io->error('Failed to import data: ' . $ex->getMessage());

            return 6;
        }

        if (!$input->getOption('no-cache')) {
            $command = $this->getApplication()->find('cache:clear');
            try {
                $command->run(new ArrayInput([]), $output);
            } catch (Exception $ex) {
                $io->error('Failed to clear cache: ' . $ex->getMessage());

                return 7;
            }
        }

        return 0;
    }

    protected function dropSchema(SymfonyStyle $io, OutputInterface $output): int
    {
        try {
            $command = $this->getApplication()->find('doctrine:schema:drop');
            $command->run(new ArrayInput(['--force' => true]), $output);
        } catch (Exception $ex) {
            $io->error('Failed to drop database schema: ' . $ex->getMessage());

            return 2;
        }

        try {
            $command = $this->getApplication()->find('doctrine:query:sql');
            $command->run(new ArrayInput(['sql' => 'DROP TABLE IF EXISTS migration_versions']), $output);
        } catch (Exception $ex) {
            $io->error('Failed to drop migration_versions table: ' . $ex->getMessage());

            return 3;
        }

        try {
            $command = $this->getApplication()->find('doctrine:query:sql');
            $command->run(new ArrayInput(['sql' => 'DROP TABLE IF EXISTS kimai2_sessions']), $output);
        } catch (Exception $ex) {
            $io->error('Failed to drop kimai2_sessions table: ' . $ex->getMessage());

            return 4;
        }

        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, string $question): bool
    {
        if (!$input->isInteractive()) {
            return true;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion('<question>' . $question . '</question>', false);

        return $questionHelper->ask($input, $output, $question);
    }

    abstract protected function getEnvName(): string;

    abstract protected function loadData(InputInterface $input, OutputInterface $output): void;
}
