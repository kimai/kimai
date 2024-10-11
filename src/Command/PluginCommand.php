<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Plugin\PluginManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch plugin information.
 */
#[AsCommand(name: 'kimai:plugins')]
final class PluginCommand extends Command
{
    public function __construct(private PluginManager $plugins)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Receive plugin information')
            ->setHelp('This command prints detailed plugin information.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
