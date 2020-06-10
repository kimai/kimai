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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Command used to update a Kimai installation.
 */
final class UpdateCommand extends Command
{
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
            ->setName('kimai:update')
            ->setDescription('Update your Kimai installation')
            ->setHelp('This command will execute all required steps to update your Kimai installation.')
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

        $io->title('Kimai updates running ...');

        /** @var Application $application */
        $application = $this->getApplication();
        /** @var KernelInterface $kernel */
        $kernel = $application->getKernel();
        $environment = $kernel->getEnvironment();

        // make sure database is available, Kimai running and installed
        try {
            if (!$this->connection->isConnected() && !$this->connection->connect()) {
                throw new \Exception(
                    sprintf('Database connection could not be established: %s', $this->connection->getDatabase())
                );
            }

            if (!$this->connection->getSchemaManager()->tablesExist(['kimai2_users', 'kimai2_timesheet'])) {
                $io->error('Tables missing. Did you run the installer already?');

                return self::ERROR_DATABASE;
            }

            if (!$this->connection->getSchemaManager()->tablesExist(['migration_versions'])) {
                $io->error('Unknown migration status, aborting database update');

                return self::ERROR_DATABASE;
            }
        } catch (\Exception $ex) {
            $io->error('Failed to validate database: ' . $ex->getMessage());

            return self::ERROR_DATABASE;
        }

        // execute latest doctrine migrations
        try {
            $command = $this->getApplication()->find('doctrine:migrations:migrate');
            $cmdInput = new ArrayInput(['--allow-no-migration' => true]);
            $cmdInput->setInteractive(false);
            if (0 !== $command->run($cmdInput, $output)) {
                throw new \RuntimeException('CRITICAL: problem when migrating database');
            }

            $io->writeln('');
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return self::ERROR_MIGRATIONS;
        }

        // flush the cache, in case values from the database are cached
        $cacheResult = $this->rebuildCaches($environment, $io, $input, $output);

        if ($cacheResult !== 0) {
            $io->warning(
                [
                    sprintf('Updated %s to version %s (%s) but the cache could not be rebuilt.', Constants::SOFTWARE, Constants::VERSION, Constants::STATUS),
                    'Please run the cache commands manually:',
                    'bin/console cache:clear --env=' . $environment . PHP_EOL .
                    'bin/console cache:warmup --env=' . $environment
                ]
            );
        } else {
            $io->success(
                sprintf('Congratulations! Successfully updated %s to version %s (%s)', Constants::SOFTWARE, Constants::VERSION, Constants::STATUS)
            );
        }

        return 0;
    }

    protected function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output)
    {
        $io->text('Rebuilding your cache, please be patient ...');

        $command = $this->getApplication()->find('cache:clear');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Could not clear cache, missing permissions?');
            }
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return self::ERROR_CACHE_CLEAN;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Could not warmup cache, missing permissions?');
            }
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return self::ERROR_CACHE_WARMUP;
        }

        return 0;
    }
}
