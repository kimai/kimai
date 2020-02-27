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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to update a Kimai installation.
 */
final class ReloadCommand extends Command
{
    public const ERROR_CACHE_CLEAN = 2;
    public const ERROR_CACHE_WARMUP = 4;
    public const ERROR_LINT_CONFIG = 8;

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
            ->setName('kimai:reload')
            ->setDescription('Reload Kimai caches')
            ->setHelp('This command will validate the current configurations and refresh the cache.')
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

        $io->title('Reloading configurations ...');

        try {
            $command = $this->getApplication()->find('lint:yaml');
            $cmdInput = new StringInput('lint:yaml --parse-tags config');
            $cmdInput->setInteractive(false);
            if (0 !== $command->run($cmdInput, $output)) {
                throw new \RuntimeException('Config file seems to be invalid');
            }

            $io->writeln('');
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return self::ERROR_LINT_CONFIG;
        }

        $environment = getenv('APP_ENV');
        if ($input->hasArgument('env')) {
            $environment = $input->getArgument('env');
        }

        // flush the cache, in case values from the database are cached
        $cacheResult = $this->rebuildCaches($environment, $io, $input, $output);

        if ($cacheResult !== 0) {
            $io->warning(
                [
                    sprintf('Cache could not be rebuilt.', Constants::SOFTWARE, Constants::VERSION, Constants::STATUS),
                    'Please run the cache commands manually:',
                    'bin/console cache:clear --env=' . $environment . PHP_EOL .
                    'bin/console cache:warmup --env=' . $environment
                ]
            );
        } else {
            $io->success(
                sprintf('Kimai config was reloaded')
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
