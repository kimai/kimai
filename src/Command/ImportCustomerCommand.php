<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Importer\ImporterService;
use App\Importer\ImportNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command can change anytime, don't rely on its API for the future!
 */
class ImportCustomerCommand extends Command
{
    private $importer;

    public function __construct(ImporterService $importer)
    {
        parent::__construct();
        $this->importer = $importer;
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
            ->addOption('no-update', null, InputOption::VALUE_NONE, 'If you want to create new customers, but not update existing ones')
        ;
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

        $skipUpdate = $input->getOption('no-update');
        $doImport = true;
        $row = 1;
        $errors = 0;
        $customers = [];
        $importer = null;

        try {
            $importer = $this->importer->getCustomerImporter($input->getOption('importer'));
            $reader = $this->importer->getReader($input->getOption('reader'));
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());

            return 1;
        }

        $importerFile = $input->getArgument('file');

        try {
            $records = $reader->read($importerFile);
        } catch (ImportNotFoundException $ex) {
            $io->error('File not existing or not readable: ' . $importerFile);

            return 2;
        }

        $amount = iterator_count($records);
        $records->rewind();
        $io->text(sprintf('Found %s rows to process, converting now ...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        foreach ($records as $record) {
            try {
                $customers[] = $importer->convertEntryToCustomer($record);
            } catch (\Exception $ex) {
                $io->error(sprintf('Invalid row %s: %s', $row, $ex->getMessage()));
                $doImport = false;
                $errors++;
            }
            $progressBar->advance();

            $row++;
        }
        $progressBar->finish();
        $io->writeln('');

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 3;
        }

        $amount = \count($customers);
        $io->text(sprintf('Converted %s customers, importing into Kimai now ...', $amount));

        $progressBar = new ProgressBar($output, $amount);

        $created = 0;
        $updated = 0;
        $noUpdatedCustomers = 0;

        foreach ($customers as $customer) {
            try {
                $progressBar->advance();

                if ($customer->getId() === null) {
                    $this->importer->importCustomer($customer);
                    $created++;
                } elseif ($skipUpdate === false) {
                    $this->importer->importCustomer($customer);
                    $updated++;
                } else {
                    $noUpdatedCustomers++;
                }
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing customer "%s" with: %s', $customer->getName(), $ex->getMessage()));

                return 4;
            }
        }

        $progressBar->finish();
        $io->writeln('');
        $io->writeln('');

        if ($created > 0) {
            $io->success(sprintf('Imported %s customer', $created));
        }
        if ($updated > 0) {
            $io->success(sprintf('Updated %s customer', $updated));
        }
        if ($noUpdatedCustomers > 0) {
            $io->success(sprintf('Skipped %s existing customer', $noUpdatedCustomers));
        }

        if ($updated === 0 && $created === 0) {
            $io->text('Nothing was imported');
        }

        return 0;
    }
}
