<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * This command is NOT used during runtime and only meant for developers on their local machines.
 * I am too lazy to think about how this could be tested ... and this is one of the rare edge cases where I don't
 * feel like it is necessary, so I "cheat" with:
 * @codeCoverageIgnore
 */
class ResetCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:reset-dev')
            ->setDescription('Resets the dev environment')
            ->setHelp(
                <<<EOT
    This command will drop and re-create the database and its schemas, load development fixtures and clear the cache.
    Use the <info>-n</info> switch to skip the question.
EOT
            )
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Skip cache flushing')
        ;
    }

    /**
     * Make sure that this command CANNOT be executed in production.
     * It can't work, as the fixtures bundle is not available in production.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return getenv('APP_ENV') !== 'prod';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->askConfirmation($input, $output, 'Do you want to create the database y/N ?')) {
            try {
                $command = $this->getApplication()->find('doctrine:database:create');
                $command->run(new ArrayInput([]), $output);
            } catch (Exception $ex) {
                $io->error('Failed to create database: ' . $ex->getMessage());

                return 1;
            }
        }

        if ($this->askConfirmation($input, $output, 'Do you want to drop and re-create the schema y/N ?')) {
            try {
                $command = $this->getApplication()->find('doctrine:schema:drop');
                $command->run(new ArrayInput(['--force' => true]), $output);
            } catch (Exception $ex) {
                $io->error('Failed to drop database schema: ' . $ex->getMessage());

                return 2;
            }

            try {
                $command = $this->getApplication()->find('doctrine:schema:create');
                $command->run(new ArrayInput([]), $output);
            } catch (Exception $ex) {
                $io->error('Failed to create database schema: ' . $ex->getMessage());

                return 3;
            }
        }

        try {
            $command = $this->getApplication()->find('doctrine:fixtures:load');
            $cmdInput = new ArrayInput([]);
            $cmdInput->setInteractive(false);
            $command->run($cmdInput, $output);
        } catch (Exception $ex) {
            $io->error('Failed to import fixtures: ' . $ex->getMessage());

            return 4;
        }

        try {
            $command = $this->getApplication()->find('doctrine:migrations:version');
            $cmdInput = new ArrayInput(['--add' => true, '--all' => true]);
            $cmdInput->setInteractive(false);
            $command->run($cmdInput, $output);
        } catch (Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return 5;
        }

        if (!$input->getOption('no-cache')) {
            $command = $this->getApplication()->find('cache:clear');
            try {
                $command->run(new ArrayInput([]), $output);
            } catch (Exception $ex) {
                $io->error('Failed to clear cache: ' . $ex->getMessage());

                return 6;
            }
        }

        return 0;
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
