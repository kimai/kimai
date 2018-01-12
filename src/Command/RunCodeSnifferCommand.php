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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to check the project coding styles.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class RunCodeSnifferCommand extends Command
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * RunCodeSnifferCommand constructor.
     * @param $projectDirectory
     */
    public function __construct($projectDirectory)
    {
        $this->rootDir = realpath($projectDirectory);
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('kimai:phpcs')
            ->setDescription('Run PHP_CodeSniffer to check for the projects coding style')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->executeCodeSniffer($io, '/src');
        $this->executeCodeSniffer($io, '/tests');
        $this->executeCodeSniffer($io, '/templates');
    }

    /**
     * @param string $directory
     */
    protected function executeCodeSniffer(SymfonyStyle $io, $directory)
    {
        $directory = $this->rootDir . $directory;

        $exitCode = 0;
        ob_start();
        passthru($this->rootDir . '/bin/phpcs --standard=PSR2 ' . $directory, $exitCode);
        $result = ob_get_clean();

        $io->write($result);

        if ($exitCode > 0) {
            $io->error('Found problems while checking sources at: ' . $directory);
        } else {
            $io->success('All sources look good at: ' . $directory);
        }
    }
}
