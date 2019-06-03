<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Doctrine\DBAL\Connection;
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
    public const ERROR_PERMISSIONS = 1;
    public const ERROR_CACHE_CLEAN = 1;
    public const ERROR_CACHE_WARMUP = 1;
    public const ERROR_DATABASE = 1;
    public const ERROR_SCHEMA = 1;
    public const ERROR_MIGRATIONS = 1;

    protected static $defaultName = 'kimai:install';
    /**
     * @var string
     */
    protected $rootDir;

    public function __construct(string $projectDirectory, Connection $connection)
    {
        parent::__construct(self::$defaultName);
        $this->rootDir = $projectDirectory;

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

        if (!$this->checkPermissions($io, $output)) {
            $question = 'Kimai found file permissions which look incorrect.' .
                ' More information is available at https://www.kimai.org/documentation/installation.html.' .
                ' If you are sure that all directories can be written by the webserver, you can continue.' .
                ' Otherwise it is recommended to abort the installation and check them first.';

            $io->error($question);

            $result = $this->askConfirmation($input, $output, '', 'Abort the installation to review permissions (yes) or continue (no)?', true);
            if ($result) {
                return self::ERROR_PERMISSIONS;
            }
        }

        $environment = $io->choice('Which environment should be used ("dev" is only for testing and imports demo data)?', ['dev', 'production'], 'production');

        $io->success(sprintf('You have chosen the "%s" environment', $environment));

        // TODO validate database connection
        exit;

        // create database if necessary
        try {
            $this->createDatabase($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return self::ERROR_DATABASE;
        }

        // create database schema
        try {
            $this->createSchema($io, $output);
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

        return 0;
    }

    protected function checkPermissions(SymfonyStyle $io, OutputInterface $output)
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
            $perms = fileperms($absDir);
            $reason = [];
            if (!($perms & 0x0100)) { $reason[] = 'read owner'; }
            if (!($perms & 0x0080)) { $reason[] = 'write owner'; }
            if (!($perms & 0x0020)) { $reason[] = 'read group'; }
            if (!($perms & 0x0010)) { $reason[] = 'read group'; }

            if (!empty($reason)) {
                $rows[] = [$directory, 'missing: ' . implode(',', $reason)];
            } elseif(!is_writable($absDir)) {
                $rows[] = [$directory, 'Directory not writable'];
            }
        }

        if (!empty($rows)) {
            $io->title('Found possible invalid permissions, please review:');
            $io->table(['Directory', 'Permission'], $rows);
            return false;
        }

        return true;
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if migrations table is existing, if so: skip
        $command = $this->getApplication()->find('doctrine:migrations:version');
        $cmdInput = new ArrayInput(['--add' => true, '--all' => true]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);

        // TODO check if there is a new migration existing, if so: ask for installation
    }

    protected function createDatabase(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if database is existing, if so: skip
        $command = $this->getApplication()->find('doctrine:database:create');
        $command->run(new ArrayInput([]), $output);
    }

    protected function createSchema(SymfonyStyle $io, OutputInterface $output)
    {
        // TODO check if schema is already existing, if so: skip
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
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $confirm, $default = false)
    {
        if (!$input->isInteractive()) {
            return true;
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]:', $confirm, $default ? 'yes' : 'no');
        $question = new ConfirmationQuestion('<question>' . $question . '</question> ' . $text . ' ', $default, '/^y|yes/i');

        return $questionHelper->ask($input, $output, $question);
    }
}
