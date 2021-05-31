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
        /** @var UserRepository $users */
        $users = $container->get(UserRepository::class);

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
            '--importer' => 'grandtotal',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Unknown project importer: grandtotal', $result);

        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testDefaultImport()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects.csv',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 3 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 3 projects, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 2 projects', $result);
        self::assertStringContainsString('[OK] Updated 1 projects', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDefaultImportSkipUpdate()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects.csv',
            '--no-update' => true,
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 3 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 3 projects, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 2 projects', $result);
        self::assertStringContainsString('[OK] Skipped 1 existing projects', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithInvalidCustomerMapping()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects_invalid.csv',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 3 rows to process, converting now ...', $result);
        self::assertStringContainsString('[ERROR] Invalid row 2: Customer mismatch for project', $result);
        self::assertStringContainsString('[CAUTION] Not importing, previous 1 errors need to be fixed first.', $result);

        self::assertEquals(3, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithSemicolon()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects2.csv',
            '--importer' => 'default',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 39 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 39 projects, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 39 projects', $result);
        self::assertStringContainsString('[OK] Imported 10 customers', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testGrandtotalImportWithInvalidCsvFile()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects.csv',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Invalid row 1: Missing customer name', $result);
        self::assertStringContainsString('! [CAUTION] Not importing, previous 3 errors need to be fixed first.', $result);

        self::assertEquals(3, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithSemicolonAndTeamlead()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects2.csv',
            '--importer' => 'default',
            '--reader' => 'csv-semicolon',
            '--teamlead' => 'clara_customer',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 39 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 39 projects, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 39 projects', $result);
        self::assertStringContainsString('[OK] Imported 10 customers', $result);
        self::assertStringContainsString('[OK] Created 39 teams', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithSemicolonAndMissingTeamlead()
    {
        $command = $this->application->find('kimai:import:project');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/projects2.csv',
            '--importer' => 'default',
            '--reader' => 'csv-semicolon',
            '--teamlead' => 'foobar',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('You requested to create empty teams for each project', $result);
        self::assertStringContainsString('Please create a user with the name (or email) foobar', $result);

        self::assertEquals(3, $commandTester->getStatusCode());
    }
}
