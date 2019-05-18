<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\BashResult;
use App\Command\RunCodestyleCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \App\Command\RunCodestyleCommand
 * @coversDefaultClass \App\Command\BashExecutor
 * @coversDefaultClass \App\Command\BashResult
 * @group integration
 */
class RunCodestyleCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;
    /**
     * @var TestBashExecutor
     */
    protected $executor;
    /**
     * @var string
     */
    protected $directory;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->directory = realpath(__DIR__ . '/../../');
        $this->executor = new TestBashExecutor($this->directory);

        $this->application->add(new RunCodestyleCommand($this->executor, $this->directory));
    }

    public function testSuccessCommandNoOptions()
    {
        $command = $this->assertSuccessCommand([]);
        $this->assertStringStartsWith('/vendor/bin/php-cs-fixer fix --dry-run --verbose --show-progress=none', $command);
    }

    public function testSuccessCommandFix()
    {
        $command = $this->assertSuccessCommand(['--fix' => true]);
        $this->assertStringStartsWith('/vendor/bin/php-cs-fixer fix', $command);
    }

    public function testSuccessCommand()
    {
        $command = $this->assertSuccessCommand([]);
        $this->assertStringStartsWith('/vendor/bin/php-cs-fixer fix --dry-run --verbose --show-progress=none', $command);
    }

    protected function assertSuccessCommand(array $options)
    {
        $result = new BashResult(0);
        $this->executor->setResult($result);

        $command = $this->application->find('kimai:codestyle');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], $options);
        $commandTester->execute($inputs);

        $output = $commandTester->getDisplay();
        $this->assertContains('[OK] All source files have proper code styles', $output);

        return $this->executor->getCommand();
    }

    public function testFailureCommand()
    {
        $result = new BashResult(1);
        $this->executor->setResult($result);

        $command = $this->application->find('kimai:codestyle');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], ['--fix' => true]);
        $commandTester->execute($inputs);

        $output = $commandTester->getDisplay();
        $this->assertContains('[ERROR] Found violations while checking code styles', $output);
    }
}
