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
class RunCodeSnifferCommand extends Command
{
    /**
     * @var string
     */
    protected $rootDir = '';

    /**
     * @param string $projectDirectory
     */
    public function __construct($projectDirectory)
    {
        $this->rootDir = realpath($projectDirectory);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:phpcs')
            ->setDescription('Run PHP_CodeSniffer to check for the projects coding style')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix all found problems (risky: modifies your files)')
            ->addOption('checkstyle', null, InputOption::VALUE_OPTIONAL, '')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $filename = null;
        $exitCode = 0;
        ob_start();

        $args = [];
        if (!$input->getOption('fix')) {
            $filename = $input->getOption('checkstyle');
            $args[] = '--dry-run';
            $args[] = '--verbose';
            $args[] = '--show-progress=none';

            if (!empty($filename)) {
                $filename = $this->rootDir . '/' . $filename;
                $args[] = '--format=checkstyle';
                if (!file_exists($filename) || is_writeable($filename)) {
                    $args[] = '> ' . $filename;
                } else {
                    $io->error('Target file is not writeable: ' . $filename);
                    return;
                }
            } else {
                $args[] = '--format=txt';
            }
        }

        passthru($this->rootDir . '/vendor/bin/php-cs-fixer fix ' . implode(' ', $args), $exitCode);
        $result = ob_get_clean();

        $io->write($result);

        if ($exitCode > 0) {
            $io->error(
                'Found problems while checking your code styles' .
                (!empty($filename) ? '. Saved checkstyle data to: ' . $filename : '')
            );
        } else {
            $io->success('All source files have proper code styles');
        }
    }
}
