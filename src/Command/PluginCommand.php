<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use App\Plugin\PluginManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch plugin information.
 */
class PluginCommand extends Command
{
    /**
     * @var PluginManager 
     */
    private $plugins;
    
    public function __construct(PluginManager $plugins)
    {
        $this->plugins = $plugins;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:plugins')
            ->setDescription('Receive plugin information')
            ->setHelp('This command prints detailed plugin information.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $plugins = $this->plugins->getPlugins();
        if (empty($plugins)) {
            $io->warning('No plugins installed');
            return 0;
        }
        
        $rows = [];
        foreach ($plugins as $plugin) {
            $this->plugins->loadMetadata($plugin);
            $meta = $plugin->getMetadata();
            $rows[] = [
                $plugin->getName(),
                $meta->getVersion(),
                $meta->getKimaiVersion(),
                $plugin->getPath(),
            ];
        }
        $io->table(['Name', 'Version', 'Requires', 'Directory'], $rows);

        return 0;
    }
}
