<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

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

        try {
            $command = $this->getApplication()->find('doctrine:database:create');
            $command->run(new ArrayInput([]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database: ' . $ex->getMessage());
            return 1;
        }

        try {
            $command = $this->getApplication()->find('doctrine:schema:create');
            $command->run(new ArrayInput([]), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to create database schema ('.$ex->getCode().'): ' . $ex->getMessage());
            return 2;
        }

        try {
            $command = $this->getApplication()->find('avanzu:admin:fetch-vendor');
            $command->run([], $output);
        } catch (\Exception $ex) {
            $io->error('Failed to fetch vendors for avanzu-admin-theme: ' . $ex->getMessage());
            return 3;
        }
        try {
            $command = $this->getApplication()->find('avanzu:admin:initialize');
            $command->run(new ArrayInput(array_merge(['--web-dir' => 'public'], $arguments)), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to initialize avanzu-admin-theme: ' . $ex->getMessage());
            return 4;
        }
        try {
            $command = $this->getApplication()->find('assets:install');
            $command->run(new ArrayInput($arguments), $output);
        } catch (\Exception $ex) {
            $io->error('Failed to install assets: ' . $ex->getMessage());
            return 5;
        }
    }
}
