<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ImportTimesheetCommand;
use App\Configuration\FormConfiguration;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ImportTimesheetCommand
 * @group integration
 */
class ImportTimesheetCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $customers = $this->createMock(CustomerRepository::class);
        $projects = $this->createMock(ProjectRepository::class);
        $activities = $this->createMock(ActivityRepository::class);
        $users = $this->createMock(UserRepository::class);
        $timesheets = $this->createMock(TimesheetRepository::class);
        $configuration = $this->createMock(FormConfiguration::class);

        $this->application->add(new ImportTimesheetCommand($customers, $projects, $activities, $users, $timesheets, $configuration));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import:timesheet');
        self::assertInstanceOf(ImportTimesheetCommand::class, $command);
    }
}
