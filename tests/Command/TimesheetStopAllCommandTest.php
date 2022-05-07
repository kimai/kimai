<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\TimesheetStopAllCommand;
use App\Tests\Mocks\TimesheetServiceFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\TimesheetStopAllCommand
 * @group integration
 */
class TimesheetStopAllCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $factory = new TimesheetServiceFactory($this);
        $service = $factory->create();

        parent::setUp();
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->application->add(new TimesheetStopAllCommand($service));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:timesheet:stop-all');
        self::assertInstanceOf(TimesheetStopAllCommand::class, $command);
    }

    public function testRun()
    {
        $command = $this->application->find('kimai:timesheet:stop-all');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[OK] Stopped 0 timesheet records.', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
