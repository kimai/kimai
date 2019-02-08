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
     * @var BashExecutor
     */
    protected $executor;
    /**
     * @var string
     */
    protected $rootDir;

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
            ->setName('kimai:test-unit')
            ->setDescription('Run all unit tests')
            ->setHelp('This command will execute all unit tests. Skips all tests with "@group integration" annotation.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->executor->execute($this->createPhpunitCmdLine());

        $io->write($result->getResult());

        if ($result->getExitCode() > 0) {
            $io->error('Found problems while running tests');

            return;
        }

        $io->success('All tests were successful');
    }

    /**
     * @return string
     */
    protected function createPhpunitCmdLine()
    {
        return '/bin/phpunit --exclude-group integration ' . $this->rootDir . '/tests';
    }
}
