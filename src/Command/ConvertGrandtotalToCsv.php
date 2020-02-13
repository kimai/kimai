<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 * @codeCoverageIgnore
 */
class ConvertGrandtotalToCsv extends Command
{
    protected static $defaultName = 'kimai:import:grandtotal-converter';

    private static $supportedHeader = [
        'Name' => '',
        'Comment' => '',
        'Number' => '',
        'Country' => '',
        'Currency' => '',
        'Vat' => '',
        'Email' => '',
        'Address' => '',
        'Contact' => '',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Convert Grandtotal customer exports to a usable format for Kimai import')
            ->setHelp(
                'Convert Grandtotal customer exports to a usable format for Kimai import'
            )
            ->addArgument('input', InputArgument::REQUIRED, 'The Grandtotal file to be converted')
            ->addArgument('output', InputArgument::REQUIRED, 'The target file to be exported')
        ;
    }

    protected function convertRow(array $row)
    {
        $result = self::$supportedHeader;
        $names = ['first' => '', 'middle' => '', 'last' => '', 'title' => ''];
        $address = ['street' => '',  'city' => '', 'code' => ''];

        foreach ($row as $name => $value) {
            switch ($name) {
                case 'Abteilung':
                case 'Briefanrede':
                case 'Bundesland':
                case 'IBAN':
                case 'BIC':
                case 'SEPA Mandat':
                    // not supported in Kimai
                    break;

                case 'Firma':
                    $result['Name'] = $value;
                    break;
                case 'E-Mail':
                    $result['Email'] = $value;
                    break;
                case 'Land':
                    $result['Country'] = $value;
                    break;
                case 'Kundennummer':
                    $result['Number'] = $value;
                    break;
                case 'Umsatzsteuer':
                    $result['Vat'] = $value;
                    break;
                case 'Notiz':
                    $result['Comment'] = strip_tags($value);
                    break;

                case 'Titel':
                    $names['title'] = $value;
                    break;
                case 'Vorname':
                    $names['first'] = $value;
                    break;
                case 'Zweiter Vorname':
                    $names['middle'] = $value;
                    break;
                case 'Nachname':
                    $names['last'] = $value;
                    break;

                case 'Straße':
                    $address['street'] = $value;
                    break;
                case 'PLZ':
                    $address['code'] = $value;
                    break;
                case 'Ort':
                    $address['city'] = $value;
                    break;
            }
        }

        $result['Address'] = trim($address['street'] . PHP_EOL . $address['code'] . ' ' . $address['city']);
        $result['Contact'] = trim(str_replace('  ', ' ', $names['title'] . ' ' . $names['first'] . ' ' . $names['middle'] . ' ' . $names['last']));

        return $result;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Kimai converter: Grandtotal');

        $csvFile = $input->getArgument('input');
        if (!file_exists($csvFile)) {
            $io->error('File not existing: ' . $csvFile);

            return 1;
        }

        if (!is_readable($csvFile)) {
            $io->error('File cannot be read: ' . $csvFile);

            return 2;
        }

        $targetFile = null;

        try {
            $targetFile = $input->getArgument('output');
            $writer = Writer::createFromPath($targetFile, 'w+');
        } catch (\Exception $ex) {
            $io->error('File cannot be written: ' . $targetFile);

            return 2;
        }

        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setDelimiter('	');
        $csv->setHeaderOffset(0);

        $writer->insertOne(array_keys(self::$supportedHeader));

        $records = $csv->getRecords();

        $doImport = true;
        $row = 0;
        $errors = 0;

        foreach ($records as $record) {
            try {
                $writer->insertOne($this->convertRow($record));
                $row++;
            } catch (\Exception $ex) {
                $io->error(sprintf('Invalid row: %s', $row));
                $errors++;
            }
        }

        if ($errors > 0) {
            $io->warning(sprintf('Failed to import %s rows', $errors));
        }
        $io->success(sprintf('Converted %s rows, saved at %s', $row, $targetFile));

        return 0;
    }
}
