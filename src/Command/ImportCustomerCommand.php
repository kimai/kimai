<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\FormConfiguration;
use App\Importer\CsvReader;
use App\Importer\DefaultCustomerImporter;
use App\Importer\GrandtotalCustomerImporter;
use App\Importer\ImportNotFoundException;
use App\Importer\ImportNotReadableException;
use App\Importer\ImportReader;
use App\Repository\CustomerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can change anytime, don't rely on its API for the future!
 *
 * @internal
 * @codeCoverageIgnore
 */
class ImportCustomerCommand extends Command
{
    private $customers;
    private $configuration;

    public function __construct(CustomerRepository $customers, FormConfiguration $configuration)
    {
        parent::__construct();
        $this->customers = $customers;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:import:customer')
            ->setDescription('Import customer from CSV file')
            ->setHelp(
                'Import customers from a CSV file.' . PHP_EOL .
                'Customer will be matched by name or number, and if not found created on the fly.' . PHP_EOL
            )
            ->addArgument('file', InputArgument::REQUIRED, 'The CSV file to be imported')
            ->addOption('importer', null, InputOption::VALUE_REQUIRED, 'The importer to use (supported: default, grandtotal)', 'default')
            ->addOption('reader', null, InputOption::VALUE_REQUIRED, 'The reader to use (supported: csv, csv-semicolon)', 'csv')
        ;
    }

    protected function getImporter(?string $importer = null)
    {
        switch ($importer) {
            case 'default':
                return new DefaultCustomerImporter($this->customers, $this->configuration);
            case 'grandtotal':
                return new GrandtotalCustomerImporter($this->customers, $this->configuration);
        }

        throw new \Exception('Unknown importer');
    }

    protected function getReader(?string $reader = null): ImportReader
    {
        switch ($reader) {
            case 'csv':
                return new CsvReader(',');
            case 'csv-semicolon':
                return new CsvReader(';');
        }

        throw new \Exception('Unknown reader');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Kimai importer: Customers');

        $doImport = true;
        $row = 1;
        $errors = 0;
        $customers = [];
        $importer = null;

        $importer = $this->getImporter($input->getOption('importer'));
        $reader = $this->getReader($input->getOption('reader'));
        $importerFile = $input->getArgument('file');

        try {
            $records = $reader->read($importerFile);
        } catch (ImportNotFoundException $ex) {
            $io->error('File not existing: ' . $importerFile);

            return 1;
        } catch (ImportNotReadableException $ex) {
            $io->error('File cannot be read: ' . $importerFile);

            return 2;
        }

        foreach ($records as $record) {
            try {
                $customers[] = $importer->convertEntryToCustomer($record);
            } catch (\Exception $ex) {
                $io->error(sprintf('Invalid row %s: %s', $row, $ex->getMessage()));
                $doImport = false;
                $errors++;
            }

            $row++;
        }

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 3;
        }

        $created = 0;
        $updated = 0;

        foreach ($customers as $customer) {
            try {
                if ($customer->getId() !== null) {
                    $updated++;
                } else {
                    $created++;
                }
                $this->customers->saveCustomer($customer);
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing customer "%s" with: %s', $customer->getName(), $ex->getMessage()));

                return 4;
            }
        }

        if ($updated > 0) {
            $io->success(sprintf('Updated %s customer', $updated));
        }
        if ($created > 0) {
            $io->success(sprintf('Imported %s customer', $created));
        }

        if ($updated === 0 && $created === 0) {
            $io->text('Nothing was imported');
        }

        return 0;
    }
}
