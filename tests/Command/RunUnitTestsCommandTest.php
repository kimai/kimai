<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\BashResult;
use App\Command\RunUnitTestsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \App\Command\RunUnitTestsCommand
 * @group integration
 */
class RunUnitTestsCommandTest extends KernelTestCase
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

        $this->application->add(new RunUnitTestsCommand($this->executor, $this->directory));
    }

    public function testSuccessCommand()
    {
        $result = new BashResult(0, 'FooBar');
        $this->executor->setResult($result);

        $command = $this->application->find('kimai:test-unit');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], []);
        $commandTester->execute($inputs);

        $output = $commandTester->getDisplay();
        $this->assertContains('FooBar', $output);
        $this->assertContains('[OK] All tests were successful', $output);

        $this->assertStringStartsWith('/bin/phpunit --exclude-group integration', $this->executor->getCommand());
        $this->assertContains($this->directory, $this->executor->getCommand());
    }

    public function testFailureCommand()
    {
        $result = new BashResult(1, 'BarFoo');
        $this->executor->setResult($result);

        $command = $this->application->find('kimai:test-unit');
        $commandTester = new CommandTester($command);
        $inputs = array_merge(['command' => $command->getName()], []);
        $commandTester->execute($inputs);

        $output = $commandTester->getDisplay();
        $this->assertContains('BarFoo', $output);
        $this->assertContains('[ERROR] Found problems while running tests', $output);

        $this->assertStringStartsWith('/bin/phpunit --exclude-group integration', $this->executor->getCommand());
        $this->assertContains($this->directory, $this->executor->getCommand());
    }
}
