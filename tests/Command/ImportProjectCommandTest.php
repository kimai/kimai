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
use Symfony\Component\Console\Tester\CommandTester;

/**
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
        $container = self::$kernel->getContainer();

        $importer = $container->get(ImporterService::class);
        $teams = $this->createMock(TeamRepository::class);
        $users = $this->createMock(UserRepository::class);

        $this->application->add(new ImportProjectCommand($importer, $teams, $users));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import:project');
        self::assertInstanceOf(ImportProjectCommand::class, $command);
    }

    public function testImportWithMissingFile()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/foo_bar.csv1'
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Kimai importer: Projects', $result);
        self::assertStringContainsString('[ERROR] File not existing or not readable', $result);
        self::assertStringContainsString('_data/foo_bar', $result);

        self::assertEquals(2, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithUnknownReader()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers2.csv',
            '--reader' => 'fooo',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Unknown import reader: fooo', $result);

        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testImportWithUnknownImporter()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers2.csv',
            '--reader' => 'fooo',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Unknown import reader: fooo', $result);

        self::assertEquals(1, $commandTester->getStatusCode());
    }
}
