<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to do the basic installation steps for Kimai.
 */
class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:install')
            ->setDescription('Basic installation for Kimai')
            ->setHelp('This command will perform the basic installation steps to get Kimai up and running.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // TODO check file permissions in var/ directories
        // TODO validate database connection

        // TODO ask for target environment
        $environment = 'prod';

        $this->createDatabase($io, $output);
        $this->createSchema($io, $output);
        $this->importMigrations($io, $output);


        // TODO check if there is a new migration existing, if so: ask for installation

        $command = $this->getApplication()->find('cache:clear');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return 6;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return 6;
        }

        return 0;
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if migrations table is existing, if so: skip
        try {
            $command = $this->getApplication()->find('doctrine:migrations:version');
            $cmdInput = new ArrayInput(['--add' => true, '--all' => true]);
            $cmdInput->setInteractive(false);
            $command->run($cmdInput, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return 5;
        }
    }

    protected function createDatabase(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if database is existing, if so: skip
        try {
            $command = $this->getApplication()->find('doctrine:database:create');
            $command->run(new ArrayInput([]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return 1;
        }
    }

    protected function createSchema(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if schema is already existing, if so: skip
        try {
            $command = $this->getApplication()->find('doctrine:schema:create');
            $command->run(new ArrayInput([]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database schema: ' . $ex->getMessage());

            return 3;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @param bool $default
     * @return bool
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default = false)
    {
        if (!$input->isInteractive()) {
            return true;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion('<question>' . $question . '</question>', $default);

        return $questionHelper->ask($input, $output, $question);
    }
}
