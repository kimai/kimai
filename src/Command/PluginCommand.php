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
use App\Plugin\PluginManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch plugin information.
 */
#[AsCommand(name: 'kimai:plugins', description: 'Manage Kimai plugins')]
final class PluginCommand extends Command
{
    public function __construct(private readonly PluginManager $plugins, private readonly PackageManager $packageManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Shows information about already installed plugins by default.');
        $this->addOption('available', null, InputOption::VALUE_NONE, 'Show list of available plugins in ' . PackageManager::PACKAGE_DIR);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('available')) {
            return $this->listPackages($io, $this->packageManager->getAvailablePackages());
        }

        return $this->listInstalledPlugins($io);
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

    private function listInstalledPlugins(SymfonyStyle $io): int
    {
        $plugins = $this->plugins->getPlugins();
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
