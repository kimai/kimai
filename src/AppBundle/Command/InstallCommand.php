<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to execute all the basic application bootstrapping AFTER "composer install" was executed.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class InstallCommand extends Command
{

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('kimai:install')
            ->setDescription('Execute all the basic installation tasks')
            ->setHelp('This command will bootstrap Kimai, copies asset installation by default')
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $arguments = [];

        if ($input->getOption('relative')) {
            $arguments = [
                '--relative' => true,
            ];
        } elseif ($input->getOption('symlink')) {
            $arguments = [
                '--symlink' => true,
            ];
        }

        $this->installAssets($output, $io, 'avanzu:admin:initialize', $arguments);
        $this->installAssets($output, $io, 'assets:install', $arguments);
        $this->installAssets($output, $io, 'avanzu:admin:fetch-vendor', []);
    }

    /**
     * @param OutputInterface $output
     * @param SymfonyStyle $io
     * @param string $cmdName
     * @param array $args
     * @return bool
     */
    protected function installAssets(OutputInterface $output, SymfonyStyle $io, $cmdName, $args = [])
    {
        $command = $this->getApplication()->find($cmdName);

        try {
            $returnCode = $command->run(new ArrayInput($args), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to install assets via "'.$cmdName.'": ' . $ex->getMessage());
            return false;
        }

        if ($returnCode != 0) {
            $io->error('Failed to install assets via "'.$cmdName.'"');
            return false;
        }

        return true;
    }
}
