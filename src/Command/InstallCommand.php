<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
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
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:install')]
final class InstallCommand extends Command
{
    public function __construct(private Connection $connection, private string $kernelEnvironment)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Basic installation for Kimai')
            ->setHelp('This command will perform the basic installation steps to get Kimai up and running.')
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Skip cache re-generation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Kimai installation running ...');

        // create the database, in case it is not yet existing
        try {
            $this->createDatabase($io, $input, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        // bootstrap database ONLY via doctrine migrations, so all installation will have the correct and same state
        try {
            $this->importMigrations($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        if (!$input->getOption('no-cache')) {
            // flush the cache, just to make sure ... and ignore result
            $this->rebuildCaches($this->kernelEnvironment, $io, $input, $output);
        }

        $io->success(
            sprintf('Congratulations! Successfully installed %s version %s', Constants::SOFTWARE, Constants::VERSION)
        );

        return Command::SUCCESS;
    }

    private function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output): int
    {
        $io->text('Rebuilding your cache, please be patient ...');

        $command = $this->getApplication()->find('cache:clear');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to warmup cache: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function importMigrations(SymfonyStyle $io, OutputInterface $output): void
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);

        $io->writeln('');
    }

    private function createDatabase(SymfonyStyle $io, InputInterface $input, OutputInterface $output): void
    {
        try {
            if ($this->connection->isConnected()) {
                $io->note(sprintf('Database is existing and connection could be established'));

                return;
            }

            if (!$this->askConfirmation($input, $output, sprintf('Create the database "%s" (yes) or skip (no)?', $this->connection->getDatabase()), true)) {
                throw new \Exception('Skipped database creation, aborting installation');
            }
        } catch (\Exception $exception) {
            // this likely means that the database does not exist. the latest doctrine release
            // changed the behavior: in previous version this code did not throw an exception.
        }

        $options = ['--if-not-exists' => true];

        $command = $this->getApplication()->find('doctrine:database:create');
        $result = $command->run(new ArrayInput($options), $output);

        if (0 !== $result) {
            throw new \Exception('Failed creating database. Check your credentials in DATABASE_URL');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @param bool $default
     * @return bool
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default = false): bool
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]:', $question, $default ? 'yes' : 'no');
        $question = new ConfirmationQuestion(' ' . $text . ' ', $default, '/^y|yes/i');

        return $questionHelper->ask($input, $output, $question);
    }
}
