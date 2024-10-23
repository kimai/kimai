<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Plugin\Package;
use App\Plugin\PackageManager;
use App\Plugin\Plugin;
use App\Plugin\PluginManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpSubprocess;

#[AsCommand(name: 'kimai:plugins', description: 'Manage Kimai plugins')]
final class PluginCommand extends Command
{
    public function __construct(
        private readonly PluginManager $pluginManager,
        private readonly PackageManager $packageManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Shows information about already installed plugins by default.');
        $this->addOption('available', null, InputOption::VALUE_NONE, 'Show list of available plugins in ' . PackageManager::PACKAGE_DIR);
        $this->addOption('composer', null, InputOption::VALUE_NONE, 'Dump list of available composer packages in ' . PackageManager::PACKAGE_DIR);
        $this->addOption('install', null, InputOption::VALUE_NONE, 'Run plugins installer, previously installed via ./kimai.sh');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('available')) {
            return $this->listPackages($io, $this->packageManager->getAvailablePackages());
        } elseif ($input->getOption('composer')) {
            return $this->listComposerPackages($io, $this->packageManager->getAvailablePackages());
        } elseif ($input->getOption('install')) {
            return $this->installPlugins($io, $output, $this->pluginManager->getPlugins());
        }

        return $this->listInstalledPlugins($io, $this->pluginManager->getPlugins());
    }

    /**
     * @param Plugin[] $plugins
     */
    private function installPlugins(SymfonyStyle $io, OutputInterface $output, array $plugins): int
    {
        foreach ($plugins as $plugin) {
            $config = $plugin->getPath() . '/migrations/doctrine_migrations.yaml';
            if (!file_exists($config)) {
                $config = $plugin->getPath() . '/Migrations/doctrine_migrations.yaml';
                if (!file_exists($config)) {
                    continue;
                }
            }

            // using getApplication()->find('doctrine:migrations:migrate') does NOT work here
            // because the Doctrine command can only be executed once
            // if run more than once it fails with a "Container is frozen" exception

            $process = new PhpSubprocess([
                'bin/console',
                'doctrine:migrations:migrate',
                '--allow-no-migration',
                '--no-interaction',
                '--configuration=' . $config
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                $io->error('Failed to install bundle database: ' . PHP_EOL . $config);
                $io->error($process->getErrorOutput());
            } else {
                if ($io->isVerbose()) {
                    $io->write($process->getOutput());
                } else {
                    $io->success('Successfully installed: ' . $plugin->getName());
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param Package[] $packages
     */
    private function listComposerPackages(SymfonyStyle $io, array $packages): int
    {
        if (empty($packages)) {
            return Command::SUCCESS;
        }

        $all = [];
        foreach ($packages as $package) {
            $all[] = $package->getMetadata()->getPackage();
        }
        $io->write(implode(' ', $all));

        return Command::SUCCESS;
    }

    /**
     * @param Package[] $packages
     */
    private function listPackages(SymfonyStyle $io, array $packages): int
    {
        if (empty($packages)) {
            $io->warning('No packages to install found');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($packages as $package) {
            $metadata = $package->getMetadata();
            $rows[] = [
                $metadata->getName(),
                $metadata->getVersion(),
                $metadata->getKimaiVersion(),
                $metadata->getPackage(),
                $package->getPackageFile()->getPathname(),
            ];
        }
        $io->table(['Name', 'Version', 'Requires', 'Package', 'Directory'], $rows);

        return Command::SUCCESS;
    }

    /**
     * @param array<Plugin> $plugins
     */
    private function listInstalledPlugins(SymfonyStyle $io, array $plugins): int
    {
        if (empty($plugins)) {
            $io->warning('No plugins installed');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($plugins as $plugin) {
            $meta = $plugin->getMetadata();
            $rows[] = [
                $plugin->getId(),
                $plugin->getName(),
                $meta->getVersion(),
                $meta->getKimaiVersion(),
                $plugin->getPath(),
            ];
        }
        $io->table(['Id', 'Name', 'Version', 'Requires', 'Directory'], $rows);

        return Command::SUCCESS;
    }
}
