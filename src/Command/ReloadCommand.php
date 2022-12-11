<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to update a Kimai installation.
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:reload')]
final class ReloadCommand extends Command
{
    public function __construct(private string $projectDirectory, private string $kernelEnvironment)
    {
        parent::__construct();
    }

    /**
     * Returns the base directory to the Kimai installation.
     *
     * @return string
     */
    protected function getRootDirectory(): string
    {
        return $this->projectDirectory;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Reload Kimai caches')
            ->setHelp('This command will validate the configurations and translations and then clear and rebuild the application cache.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Reloading configurations ...');

        // many users execute the bin/console command from arbitrary locations
        $path = getcwd();
        chdir($this->getRootDirectory());

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

            return Command::FAILURE;
        }

        try {
            $command = $this->getApplication()->find('lint:xliff');
            $cmdInput = new StringInput('lint:xliff translations');
            $cmdInput->setInteractive(false);
            if (0 !== $command->run($cmdInput, $output)) {
                throw new \RuntimeException('Translation files seem to be invalid');
            }

            $io->writeln('');
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        }

        $environment = $this->kernelEnvironment;

        // flush the cache, in case values from the database are cached
        $cacheResult = $this->rebuildCaches($environment, $io, $input, $output);

        chdir($path);

        if ($cacheResult !== 0) {
            $io->warning(
                [
                    'Cache could not be rebuilt.',
                    'Please run these commands to rebuild the cache manually:',
                    'rm -r var/cache/*' . PHP_EOL .
                    'bin/console cache:clear --env=' . $environment . PHP_EOL .
                    'bin/console cache:warmup --env=' . $environment
                ]
            );

            return (int) $cacheResult;
        }

        $io->success(
            sprintf('Kimai config was reloaded')
        );

        return Command::SUCCESS;
    }

    private function rebuildCaches(string $environment, SymfonyStyle $io, InputInterface $input, OutputInterface $output)
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
