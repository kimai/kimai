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
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to update a Kimai installation.
 */
#[AsCommand(name: 'kimai:update')]
final class UpdateCommand extends Command
{
    public function __construct(private Connection $connection, private string $kernelEnvironment)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update your Kimai installation')
            ->setHelp('This command will execute all required steps to update your Kimai installation.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Kimai updates running ...');

        $environment = $this->kernelEnvironment;

        // make sure database is available, Kimai running and installed
        try {
            if (!$this->connection->createSchemaManager()->tablesExist(['kimai2_users', 'kimai2_timesheet'])) {
                $io->error('Tables missing. Did you run the installer already?');

                return Command::FAILURE;
            }

            if (!$this->connection->createSchemaManager()->tablesExist(['migration_versions'])) {
                $io->error('Unknown migration status, aborting database update');

                return Command::FAILURE;
            }
        } catch (ConnectionException $e) {
            $io->error(['Database connection could not be established.', $e->getMessage()]);

            return Command::FAILURE;
        } catch (\Exception $ex) {
            $io->error(['Failed to validate database.', $ex->getMessage()]);

            return Command::FAILURE;
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

            return Command::FAILURE;
        }

        // flush the cache, in case values from the database are cached
        $cacheResult = $this->rebuildCaches($environment, $io, $input, $output);

        if ($cacheResult !== Command::SUCCESS) {
            $io->warning(
                [
                    sprintf('Updated %s to version %s but the cache could not be rebuilt.', Constants::SOFTWARE, Constants::VERSION),
                    'Please run the cache commands manually:',
                    'bin/console cache:clear --env=' . $environment . PHP_EOL .
                    'bin/console cache:warmup --env=' . $environment
                ]
            );
        } else {
            $io->success(
                sprintf('Congratulations! Successfully updated %s to version %s', Constants::SOFTWARE, Constants::VERSION)
            );
        }

        return Command::SUCCESS;
    }

    private function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output): int
    {
        $io->text('Rebuilding your cache, please be patient ...');

        $command = $this->getApplication()->find('cache:clear');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Could not clear cache, missing permissions?');
            }
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        }

        $command = $this->getApplication()->find('cache:warmup');
        try {
            if (0 !== $command->run(new ArrayInput(['--env' => $environment]), $output)) {
                throw new \RuntimeException('Could not warmup cache, missing permissions?');
            }
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
