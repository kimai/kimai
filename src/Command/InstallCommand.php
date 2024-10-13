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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to do the basic installation steps for Kimai.
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:install', description: 'Kimai installation command', aliases: ['kimai:update'])]
final class InstallCommand extends Command
{
    public function __construct(private readonly Connection $connection, private readonly string $kernelEnvironment)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command will perform the installation steps to bootstrap the application, database and plugins.')
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Skip cache re-generation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Start installation ...');

        try {
            // creates the database if it is not yet existing
            $this->createDatabase($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        try {
            // bootstrap database ONLY via doctrine migrations, so all installation will have the same state
            $this->importMigrations($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        if (!$input->getOption('no-cache')) {
            // show manual steps in case this fails
            $cacheResult = $this->rebuildCaches($this->kernelEnvironment, $io, $input, $output);

            if ($cacheResult !== Command::SUCCESS) {
                $io->warning(
                    [
                        'Please run the cache commands manually:',
                        'bin/console cache:clear --env=' . $this->kernelEnvironment . PHP_EOL .
                        'bin/console cache:warmup --env=' . $this->kernelEnvironment
                    ]
                );
            }
        }

        $io->success('Installed database 👍');

        // TODO search local plugins and install them via composer

        $io->success(
            \sprintf('Successfully installed %s version %s 🎉', Constants::SOFTWARE, Constants::VERSION)
        );

        return Command::SUCCESS;
    }

    private function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output): int
    {
        $io->text('Rebuilding your cache ...');

        $command = $this->getApplication()->find('cache:clear');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Invalid file permissions?');
            }
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Invalid file permissions?');
            }
        } catch (\Exception $ex) {
            $io->error('Failed to warmup cache: ' . $ex->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function importMigrations(SymfonyStyle $io, OutputInterface $output): void
    {
        try {
            if (!$this->connection->createSchemaManager()->tablesExist(['migration_versions'])) {
                throw new \RuntimeException('Migration table does not exist');
            }
        } catch (\Exception $ex) {
            $io->error(['Failed to detect migration status, aborting update.', $ex->getMessage()]);

            return;
        }

        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true]);
        $cmdInput->setInteractive(false);
        $result = $command->run($cmdInput, $output);

        if (0 !== $result) {
            throw new \Exception('Failed updating database.');
        }
    }

    private function createDatabase(SymfonyStyle $io, OutputInterface $output): void
    {
        try {
            if ($this->connection->isConnected()) {
                $io->note(\sprintf('Database is existing and connection could be established'));

                return;
            }
        } catch (\Exception $ex) {
            // this likely means that the database does not exist and the connection failed
        }

        $command = $this->getApplication()->find('doctrine:database:create');
        $cmdInput = new ArrayInput(['--if-not-exists' => true]);
        $cmdInput->setInteractive(false);
        $result = $command->run($cmdInput, $output);

        if (0 !== $result) {
            throw new \Exception('Failed creating database: check your DATABASE_URL.');
        }
    }
}
