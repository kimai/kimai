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
    public const ERROR_MIGRATIONS = 32;

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
        parent::__construct();
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

        $io->title('Kimai installation running ...');

        $environment = getenv('APP_ENV');

        // create the database, in case it is not yet existing
        try {
            $this->createDatabase($io, $input, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());

            return self::ERROR_DATABASE;
        }

        // bootstrap database ONLY via doctrine migrations, so all installation will have the correct and same state
        try {
            $this->importMigrations($io, $output);
        } catch (\Exception $ex) {
            $io->error('Failed to set migration status: ' . $ex->getMessage());

            return self::ERROR_MIGRATIONS;
        }

        // flush the cache, just to make sure ... and ignore result
        $this->rebuildCaches($environment, $io, $input, $output);

        $io->success(
            sprintf('Congratulations! Successfully installed %s version %s (%s)', Constants::SOFTWARE, Constants::VERSION, Constants::STATUS)
        );

        return 0;
    }

    protected function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $io->text('Rebuilding your cache, please be patient ...');

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
            $io->error('Failed to warmup cache: ' . $ex->getMessage());

            return self::ERROR_CACHE_WARMUP;
        }

        return 0;
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true]);
        $cmdInput->setInteractive(false);
        $command->run($cmdInput, $output);

        $io->writeln('');
    }

    protected function createDatabase(SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        if (!$this->connection->isConnected() && !$this->connection->connect()) {
            throw new \Exception(
                sprintf('Database connection could not be established: %s', $this->connection->getDatabase())
            );
        }

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
