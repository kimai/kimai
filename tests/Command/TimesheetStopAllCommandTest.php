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
}
