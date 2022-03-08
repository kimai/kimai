<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch Kimai version information.
 */
class VersionCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:version')
            ->setDescription('Receive version information')
            ->setHelp('This command allows you to fetch various version information about Kimai.')
            ->addOption('short', null, InputOption::VALUE_NONE, 'Display the version only')
            ->addOption('number', null, InputOption::VALUE_NONE, 'Display the version identifier only only')
            // @deprecated since 1.14.1
            ->addOption('name', null, InputOption::VALUE_NONE, 'DEPRECATED: Display the major release name')
            ->addOption('candidate', null, InputOption::VALUE_NONE, 'DEPRECATED: Display the current version candidate (e.g. "stable" or "dev")')
            ->addOption('semver', null, InputOption::VALUE_NONE, 'DEPRECATED: Semantical versioning (SEMVER) compatible version string')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('semver')) {
            @trigger_error('bin/console kimai:version --semver is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
            $io->writeln(Constants::VERSION . '-' . Constants::STATUS);

            return 0;
        }

        if ($input->getOption('short')) {
            $io->writeln(Constants::VERSION);

            return 0;
        }

        if ($input->getOption('name')) {
            @trigger_error('bin/console kimai:version --name is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
            $io->writeln(Constants::NAME);

            return 0;
        }

        if ($input->getOption('candidate')) {
            @trigger_error('bin/console kimai:version --candidate is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
            $io->writeln(Constants::STATUS);

            return 0;
        }

        if ($input->getOption('number')) {
            $io->writeln((string) Constants::VERSION_ID);

            return 0;
        }

        $io->writeln(sprintf('%s <info>%s</info> by Kevin Papst and contributors.', Constants::SOFTWARE, Constants::VERSION));

        return 0;
    }
}
