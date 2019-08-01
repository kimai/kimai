<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use App\Utils\File;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to do the basic installation steps for Kimai.
 */
class InstallCommand extends Command
{
    public const ERROR_PERMISSIONS = 1;
    public const ERROR_CACHE_CLEAN = 2;
    public const ERROR_CACHE_WARMUP = 4;
    public const ERROR_DATABASE = 8;
    public const ERROR_SCHEMA = 16;
    public const ERROR_MIGRATIONS = 32;
    public const ERROR_INTERACTIVE = 64;

    protected static $defaultName = 'kimai:install';
    /**
     * @var string
     */
    protected $rootDir;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var File
     */
    protected $file;

    public function __construct(string $projectDirectory, Connection $connection, File $files)
    {
        parent::__construct(self::$defaultName);
        $this->rootDir = $projectDirectory;
        $this->connection = $connection;
        $this->file = $files;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
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

        $io->title('Kimai installer - v' . Constants::VERSION);

        $result = $this->reviewPermissions($io, $input, $output);
        if (true !== $result) {
            return $result;
        }

        // we cannot change the environment here, as it needs to be configured in the .env file before this command is started
        // $environment = $io->choice('Which environment should be used ("dev" is only for testing and imports demo data)?', ['dev', 'production'], 'production');
        // $io->note(sprintf('You have chosen the "%s" environment', $environment));
        $environment = getenv('APP_ENV');

        try {
            $this->createDatabase($io, $input, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return self::ERROR_DATABASE;
        }

        try {
            $this->createSchema($io, $input, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database schema: ' . $ex->getMessage());

            return self::ERROR_SCHEMA;
        }

        // initialize database with proper migration status
        try {
            $this->importMigrations($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return self::ERROR_MIGRATIONS;
        }

        $this->rebuildCaches($environment, $io, $input, $output);

        $io->success(
            'Congratulations! ' . Constants::SOFTWARE . ' (' . Constants::VERSION . ' ' . Constants::STATUS . ') was successful installed!'
        );

        return 0;
    }

    protected function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        if ($input->isInteractive()) {
            $question = 'Do you want me to rebuild the caches (yes) or skip this step (no)?';
            if (!$this->askConfirmation($input, $output, $question, true)) {
                return;
            }
        }

        $io->text('Rebuilding your cache now, please be patient ...');

        $command = $this->getApplication()->find('cache:clear');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return self::ERROR_CACHE_CLEAN;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            $command->run(new ArrayInput(['--env' => $environment]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to clear cache: ' . $ex->getMessage());

            return self::ERROR_CACHE_WARMUP;
        }
    }

    protected function checkPermissions(): array
    {
        $directories = [
            'var/cache/',
            'var/data/',
            'var/log/',
            'var/plugins/',
            'var/sessions/',
        ];

        $rows = [];

        foreach ($directories as $directory) {
            $absDir = rtrim($this->rootDir) . DIRECTORY_SEPARATOR . $directory;
            $perms = $this->file->getPermissions($absDir);
            $reason = [];
            if (!($perms & 0x0100)) {
                $reason[] = 'read owner';
            }
            if (!($perms & 0x0080)) {
                $reason[] = 'write owner';
            }
            if (!($perms & 0x0020)) {
                $reason[] = 'read group';
            }
            if (!($perms & 0x0010)) {
                $reason[] = 'write group';
            }

            if (!empty($reason)) {
                $rows[] = [$directory, 'missing: ' . implode(',', $reason)];
            } elseif (!is_writable($absDir)) {
                $rows[] = [$directory, 'Directory not writable'];
            }
        }

        return $rows;
    }

    protected function reviewPermissions(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive()) {
            return true;
        }

        $permissions = $this->checkPermissions();

        if (empty($permissions)) {
            return true;
        }

        $question = 'Kimai found file permissions which look incorrect.' .
            ' More information is available at https://www.kimai.org/documentation/installation.html.' .
            ' If you are sure that all directories can be written by the webserver, you can continue.' .
            ' Otherwise it is recommended to abort the installation and check them first.';

        $io->caution($question);

        $io->table(['Directory', 'Permission'], $permissions);

        if (!$this->askConfirmation($input, $output, 'Continue with the installation (yes) or review permissions first (no)?', false)) {
            $io->warning('Aborting installation to review the permissions for above mentioned directories');

            return self::ERROR_PERMISSIONS;
        }
        $io->writeln('');

        return true;
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        if (!$this->connection->getSchemaManager()->tablesExist(['migration_versions'])) {
            $command = $this->getApplication()->find('doctrine:migrations:version');
            $cmdInput = new ArrayInput(['--add' => true, '--all' => true]);
            $cmdInput->setInteractive(false);
            $command->run($cmdInput, $output);

            return;
        }

        // this case should not happen, but you know ... everything is possible
        $amount = $this->connection->executeQuery('SELECT count(*) as counter FROM migration_versions')->fetchColumn(0);
        if ($amount === 0) {
            $command = $this->getApplication()->find('doctrine:migrations:version');
            $cmdInput = new ArrayInput(['--add' => true, '--all' => true]);
            $cmdInput->setInteractive(false);
            $command->run($cmdInput, $output);

            return;
        }

        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);

        $io->writeln('');
    }

    protected function createDatabase(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        if ($this->connection->isConnected()) {
            $io->note(sprintf('Database is existing and connection could be established'));

            return;
        }

        if (!$this->askConfirmation($input, $output, sprintf('Create the database "%s" (yes) or skip (no)?', $this->connection->getDatabase()), true)) {
            throw new \Exception('Skipped database creation, aborting installation');
        }

        $command = $this->getApplication()->find('doctrine:database:create');
        $command->run(new ArrayInput([]), $output);
    }

    protected function createSchema(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        if (!$this->connection->isConnected() && !$this->connection->connect()) {
            throw new \Exception(sprintf('Cannot create tables in database "%s", connection could not be established', $this->connection->getDatabase()));
        }

        if ($this->connection->getSchemaManager()->tablesExist(['kimai2_users', 'kimai2_timesheet'])) {
            $io->note('It seems as if you already have the required tables in your database, skipping schema creation');

            return;
        }

        $command = $this->getApplication()->find('doctrine:schema:create');
        $command->run(new ArrayInput([]), $output);
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
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]:', $question, $default ? 'yes' : 'no');
        $question = new ConfirmationQuestion(' ' . $text . ' ', $default, '/^y|yes/i');

        return $questionHelper->ask($input, $output, $question);
    }
}
