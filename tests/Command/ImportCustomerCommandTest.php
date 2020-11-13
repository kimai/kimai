<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Command;

use App\Command\ImportCustomerCommand;
use App\Importer\ImporterService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group integration
 */
class ImportCustomerCommandTest extends KernelTestCase
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

        $this->application->add(new ImportCustomerCommand($importer));
    }

    public function testCommandName()
    {
        $command = $this->application->find('kimai:import:customer');
        self::assertInstanceOf(ImportCustomerCommand::class, $command);
    }

    public function testImportWithMissingFile()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/foo_bar.csv1'
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Kimai importer: Customers', $result);
        self::assertStringContainsString('[ERROR] File not existing or not readable', $result);
        self::assertStringContainsString('_data/foo_bar', $result);

        self::assertEquals(2, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithUnknownReader()
    {
        $command = $this->application->find('kimai:import:customer');
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
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers2.csv',
            '--importer' => 'fooo',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('[ERROR] Unknown customer importer: fooo', $result);

        self::assertEquals(1, $commandTester->getStatusCode());
    }

    public function testDefaultImport()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers.csv'
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 10 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 10 customers, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 9 customer', $result);
        self::assertStringContainsString('[OK] Updated 1 customer', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDefaultImportSkipUpdate()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers.csv',
            '--no-update' => true,
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 10 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 10 customers, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 9 customer', $result);
        self::assertStringContainsString('[OK] Skipped 1 existing customer', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDefaultImportWithSemicolon()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers2.csv',
            '--importer' => 'default',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 10 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 10 customers, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 10 customer', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testGrandtotalImportWithInvalidCsvFile()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/customers2.csv',
            '--importer' => 'grandtotal',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Invalid row 1: Missing customer name', $result);
        self::assertStringContainsString('! [CAUTION] Not importing, previous 10 errors need to be fixed first.', $result);

        self::assertEquals(3, $commandTester->getStatusCode());
    }

    public function testGrandtotalImport()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/grandtotal_en.csv',
            '--importer' => 'grandtotal',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 1 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 1 customers, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 1 customer', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }

    public function testGrandtotalImportGerman()
    {
        $command = $this->application->find('kimai:import:customer');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
            'file' => __DIR__ . '/../Importer/_data/grandtotal_de.csv',
            '--importer' => 'grandtotal',
            '--reader' => 'csv-semicolon',
        ]);

        $result = $commandTester->getDisplay();

        self::assertStringContainsString('Found 2 rows to process, converting now ...', $result);
        self::assertStringContainsString('Converted 2 customers, importing into Kimai now ...', $result);
        self::assertStringContainsString('[OK] Imported 1 customer', $result);
        self::assertStringContainsString('[OK] Updated 1 customer', $result);

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
