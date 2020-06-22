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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Command used to do the basic installation steps for Kimai.
 */
final class InstallCommand extends Command
{
    public const ERROR_PERMISSIONS = 1;
    public const ERROR_CACHE_CLEAN = 2;
    public const ERROR_CACHE_WARMUP = 4;
    public const ERROR_DATABASE = 8;
    public const ERROR_MIGRATIONS = 32;

    /**
     * @var string
     */
    private $rootDir;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(string $projectDirectory, Connection $connection)
    {
        parent::__construct();
        $this->rootDir = $projectDirectory;
        $this->connection = $connection;
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
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Skip cache re-generation')
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

        /** @var Application $application */
        $application = $this->getApplication();
        /** @var KernelInterface $kernel */
        $kernel = $application->getKernel();
        $environment = $kernel->getEnvironment();

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

        if (!$input->getOption('no-cache')) {
            // flush the cache, just to make sure ... and ignore result
            $this->rebuildCaches($environment, $io, $input, $output);
        }

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
        if ($this->connection->isConnected()) {
            $io->note(sprintf('Database is existing and connection could be established'));

            return;
        }

        if (!$this->askConfirmation($input, $output, sprintf('Create the database "%s" (yes) or skip (no)?', $this->connection->getDatabase()), true)) {
            throw new \Exception('Skipped database creation, aborting installation');
        }

        $options = [];
        if ($this->connection->getDatabasePlatform()->getName() !== 'sqlite') {
            $options = ['--if-not-exists' => true];
        }

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
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default = false)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $text = sprintf('<info>%s (yes/no)</info> [<comment>%s</comment>]:', $question, $default ? 'yes' : 'no');
        $question = new ConfirmationQuestion(' ' . $text . ' ', $default, '/^y|yes/i');

        return $questionHelper->ask($input, $output, $question);
    }
}
