<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Configuration\FormConfiguration;
use App\Entity\Customer;
use App\Importer\InvalidFieldsException;
use App\Repository\CustomerRepository;
use League\Csv\Reader;
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
    protected static $defaultName = 'kimai:import:customer';

    private static $requiredHeader = [
        'Name',
    ];

    private static $supportedHeader = [
        'Name',
        'Comment',
        'Number',
        'Country',
        'Currency',
        'Vat',
        'Email',
        'Address',
        'Contact',
        'Timezone',
    ];

    /**
     * @var CustomerRepository
     */
    private $customers;
    /**
     * @var FormConfiguration
     */
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
            ->setName(self::$defaultName)
            ->setDescription('Import customer from CSV file')
            ->setHelp(
                'This command allows to import customers from a CSV file and create empty teams for each of them.' . PHP_EOL .
                'Imported customer will be matched by name and optionally created on the fly.' . PHP_EOL .
                'Required column names: ' . implode(', ', self::$requiredHeader) . PHP_EOL .
                'Supported column names: ' . implode(', ', self::$supportedHeader) . PHP_EOL
            )
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'The CSV field delimiter', ',')
            ->addArgument('file', InputArgument::REQUIRED, 'The CSV file to be imported')
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

        $csvFile = $input->getArgument('file');
        if (!file_exists($csvFile)) {
            $io->error('File not existing: ' . $csvFile);

            return 1;
        }

        if (!is_readable($csvFile)) {
            $io->error('File cannot be read: ' . $csvFile);

            return 2;
        }

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setDelimiter($input->getOption('delimiter'));
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();

        if (!$this->validateHeader($header)) {
            $io->error(
                sprintf(
                    'Found invalid CSV header: %s' . PHP_EOL .
                    'Required fields: %s' . PHP_EOL .
                    'All supported fields: %s' . PHP_EOL,
                    implode(', ', $header),
                    implode(', ', self::$requiredHeader),
                    implode(', ', self::$supportedHeader)
                )
            );

            return 4;
        }

        $records = $csv->getRecords();

        $doImport = true;
        $row = 1;
        $errors = 0;

        foreach ($records as $record) {
            try {
                $this->validateRow($record);
            } catch (InvalidFieldsException $ex) {
                $io->error(sprintf('Invalid row %s, invalid fields: %s', $row, implode(', ', $ex->getFields())));
                $doImport = false;
                $errors++;
            }

            $row++;
        }

        if (!$doImport) {
            $io->caution(sprintf('Not importing, previous %s errors need to be fixed first.', $errors));

            return 5;
        }

        $created = 0;
        $updated = 0;
        foreach ($records as $record) {
            $row++;
            try {
                $tmpCustomer = $this->customers->findBy(['name' => $record['Name']]);

                if (count($tmpCustomer) !== 1 && isset($record['Number']) && !empty($record['Number'])) {
                    $tmpCustomer = $this->customers->findBy(['number' => $record['Number']]);
                }

                $customer = null;

                if (count($tmpCustomer) > 1) {
                    throw new \Exception(
                        sprintf('Found multiple matching customers by name "%s" or number "%s"', $record['Name'], $record['Number'])
                    );
                } elseif (count($tmpCustomer) === 1) {
                    $updated++;
                    $customer = $tmpCustomer[0];
                } else {
                    $created++;
                    $customer = new Customer();
                }

                $customer->setName($record['Name']);

                if (isset($record['Comment']) && !empty($record['Comment'])) {
                    $customer->setComment($record['Comment']);
                }

                if (isset($record['Number']) && !empty($record['Number'])) {
                    $customer->setNumber($record['Number']);
                }

                if (isset($record['Country']) && !empty($record['Country'])) {
                    $customer->setCountry($record['Country']);
                } else {
                    $customer->setCountry($this->configuration->getCustomerDefaultCountry());
                }

                if (isset($record['Currency']) && !empty($record['Currency'])) {
                    $customer->setCurrency($record['Currency']);
                } else {
                    $customer->setCurrency($this->configuration->getCustomerDefaultCurrency());
                }

                if (isset($record['Vat']) && !empty($record['Vat'])) {
                    $customer->setVatId($record['Vat']);
                }

                if (isset($record['Email']) && !empty($record['Email'])) {
                    $customer->setEmail($record['Email']);
                }

                if (isset($record['Address']) && !empty($record['Address'])) {
                    $customer->setAddress($record['Address']);
                }

                if (isset($record['Contact']) && !empty($record['Contact'])) {
                    $customer->setContact($record['Contact']);
                }

                $timezone = date_default_timezone_get();
                if (isset($record['Timezone'])) {
                    $timezone = $record['Timezone'];
                } elseif (null !== $this->configuration->getCustomerDefaultTimezone()) {
                    $timezone = $this->configuration->getCustomerDefaultTimezone();
                }
                $customer->setTimezone($timezone);

                $this->customers->saveCustomer($customer);
            } catch (\Exception $ex) {
                $io->error(sprintf('Failed importing customer row %s with: %s', $row, $ex->getMessage()));

                return 6;
            }
        }

        $io->success(sprintf('Updated %s customers', $updated));
        $io->success(sprintf('Imported %s customers', $created));

        return 0;
    }

    /**
     * @param array $row
     * @return bool
     * @throws InvalidFieldsException
     */
    private function validateRow(array $row)
    {
        $fields = [];

        foreach (self::$requiredHeader as $headerName) {
            if (!isset($row[$headerName]) || empty($row[$headerName])) {
                $fields[] = $headerName;
            }
        }

        if (!empty($fields)) {
            throw new InvalidFieldsException($fields);
        }

        return true;
    }

    private function validateHeader(array $header)
    {
        $fields = [];

        foreach (self::$requiredHeader as $headerName) {
            if (!in_array($headerName, $header)) {
                $fields[] = $headerName;
            }
        }

        return empty($fields);
    }
}
