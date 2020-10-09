<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ImportProjectCommand;
use App\Importer\ImporterService;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Command\ImportProjectCommand
 * @group integration
 */
class ImportProjectCommandTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $importer = $this->createMock(ImporterService::class);
        $teams = $this->createMock(TeamRepository::class);
        $users = $this->createMock(UserRepository::class);

        $this->application->add(new ImportProjectCommand($importer, $teams, $users));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import:project');
        self::assertInstanceOf(ImportProjectCommand::class, $command);
    }
}
