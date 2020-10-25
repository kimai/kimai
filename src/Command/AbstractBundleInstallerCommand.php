<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Extend this class if you have a plugin that requires installation steps.
 */
abstract class AbstractBundleInstallerCommand extends Command
{
    /**
     * Returns the base directory to the Kimai installation.
     *
     * @return string
     */
    protected function getRootDirectory(): string
    {
        /** @var Application $application */
        $application = $this->getApplication();

        return $application->getKernel()->getProjectDir();
    }

    /**
     * If your bundle ships assets, that need to be available in the public/ directory,
     * then overwrite this method and return: <true>.
     *
     * @return bool
     */
    protected function hasAssets(): bool
    {
        return false;
    }

    /**
     * Returns an absolute filename to your doctrine migrations configuration, if you want to install database tables.
     *
     * @return string|null
     */
    protected function getMigrationConfigFilename(): ?string
    {
        return null;
    }

    /**
     * Returns the bundle short name for the installer command.
     *
     * @return string
     */
    abstract protected function getBundleCommandNamePart(): string;

    /**
     * Returns the full name fo this command.
     * Please stick to the standard and overwrite getBundleCommandNamePart() only.
     *
     * @return string
     */
    protected function getInstallerCommandName(): string
    {
        return sprintf('kimai:bundle:%s:install', $this->getBundleCommandNamePart());
    }

    /**
     * Returns the bundles real name (same as your namespace).
     *
     * @return string
     */
    protected function getBundleName(): string
    {
        $class = new \ReflectionClass($this);
        $parts = explode('\\', $class->getNamespaceName());

        if ($parts[0] !== 'KimaiPlugin') {
            throw new LogicException(
                sprintf('Unsupported namespace given, expected "KimaiPlugin" but received "%s". Please overwrite getBundleName() and return the correct bundle name.', $parts[0])
            );
        }

        return $parts[1];
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->getInstallerCommandName())
            ->setDescription('Install the bundle: ' . $this->getBundleName())
            ->setHelp('This command will perform the basic installation steps to get the bundle up and running.')
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

        // many users execute the bin/console command from arbitrary locations
        // this will make sure that relative paths (like doctrine migrations) work as expected
        $path = getcwd();
        chdir($this->getRootDirectory());

        $bundleName = $this->getBundleName();
        $io->title(
            sprintf('Starting installation of plugin: %s ...', $bundleName)
        );

        try {
            $this->importMigrations($io, $output);
        } catch (\Exception $ex) {
            $io->error(
                sprintf('Failed to install database for bundle %s. %s', $bundleName, $ex->getMessage())
            );

            return 1;
        }

        if ($this->hasAssets()) {
            try {
                $this->installAssets($io, $output);
            } catch (\Exception $ex) {
                $io->error(
                    sprintf('Failed to install assets for bundle %s. %s', $bundleName, $ex->getMessage())
                );

                return 1;
            }
        }

        chdir($path);

        $io->success(
            sprintf('Congratulations! Plugin was successful installed: %s', $bundleName)
        );

        return 0;
    }

    protected function installAssets(SymfonyStyle $io, OutputInterface $output)
    {
        $command = $this->getApplication()->find('assets:install');
        $cmdInput = new ArrayInput([]);
        $cmdInput->setInteractive(false);
        if (0 !== $command->run($cmdInput, $output)) {
            throw new \Exception('Problem occurred while installing assets.');
        }

        $io->writeln('');
    }

    protected function importMigrations(SymfonyStyle $io, OutputInterface $output)
    {
        $config = $this->getMigrationConfigFilename();

        if (null === $config) {
            return false;
        }

        if (!file_exists($config)) {
            throw new FileNotFoundException('Missing doctrine migrations config file: ' . $config);
        }

        // prevent windows from breaking
        $config = str_replace('/', DIRECTORY_SEPARATOR, $config);

        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $cmdInput = new ArrayInput(['--allow-no-migration' => true, '--configuration' => $config]);
        $cmdInput->setInteractive(false);
        if (0 !== $command->run($cmdInput, $output)) {
            throw new \Exception('Problem occurred while executing migrations.');
        }

        $io->writeln('');
    }
}
