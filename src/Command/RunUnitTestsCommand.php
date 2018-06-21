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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to run all unit tests.
 */
class RunUnitTestsCommand extends Command
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * RunCodeSnifferCommand constructor.
     * @param string $projectDirectory
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
            ->setName('kimai:test-unit')
            ->setDescription('Run all unit tests')
            ->setHelp('This command will execute all unit tests. Skips all tests with "@group integration" annotation.')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->executeTests($io, '/tests');
    }

    /**
     * @param $directory
     * @return string
     */
    protected function createPhpunitCmdLine($directory)
    {
        return $this->rootDir . '/bin/phpunit --exclude-group integration ' . $directory;
    }

    /**
     * @param string $directory
     */
    protected function executeTests(SymfonyStyle $io, $directory)
    {
        $directory = $this->rootDir . $directory;

        $exitCode = 0;
        ob_start();
        passthru($this->createPhpunitCmdLine($directory), $exitCode);
        $result = ob_get_clean();

        $io->write($result);

        if ($exitCode > 0) {
            $io->error('Found problems while running tests at: ' . $directory);
        } else {
            $io->success('All tests performed good at: ' . $directory);
        }
    }
}
