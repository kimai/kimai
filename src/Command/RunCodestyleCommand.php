<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to check and apply the projects coding styles.
 */
class RunCodestyleCommand extends Command
{
    /**
     * @var BashExecutor
     */
    protected $executor;
    /**
     * @var string
     */
    protected $rootDir = '';

    /**
     * @param BashExecutor $executor
     * @param string $projectDirectory
     */
    public function __construct(BashExecutor $executor, string $projectDirectory)
    {
        $this->executor = $executor;
        $this->rootDir = realpath($projectDirectory);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:codestyle')
            ->setDescription('Check and fix the projects coding style')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix all found problems')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $args = [];
        if (!$input->getOption('fix')) {
            $args[] = '--dry-run';
            $args[] = '--verbose';
            $args[] = '--show-progress=none';
        }

        $result = $this->executor->execute('/vendor/bin/php-cs-fixer fix ' . implode(' ', $args));

        if ($result->getExitCode() > 0) {
            $io->error('Found violations while checking code styles');

            return;
        }

        $io->success('All source files have proper code styles');
    }
}
