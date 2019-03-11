<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\InvoiceTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
class InvoiceFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getInvoiceConfigs() as $invoiceConfig) {
            $template = new InvoiceTemplate();

            // name, title, renderer, calculator, numberGenerator, company, vat, dueDays, address, paymentTerms
            $template
                ->setName($invoiceConfig[0])
                ->setTitle($invoiceConfig[1])
                ->setRenderer($invoiceConfig[2])
                ->setCalculator($invoiceConfig[3])
                ->setNumberGenerator($invoiceConfig[4])
                ->setCompany($invoiceConfig[5])
                ->setVat($invoiceConfig[6])
                ->setDueDays($invoiceConfig[7])
                ->setAddress($invoiceConfig[8])
                ->setPaymentTerms($invoiceConfig[9])
            ;

            $manager->persist($template);
            $manager->flush();
        }
    }

    private function getInvoiceConfigs()
    {
        $faker = Factory::create();

        $paymentTerms =
            'I would like to thank you for your confidence and will gladly be there for you in the future.' .
            PHP_EOL .
            'Please transfer the total amount within 14 days to the given account and use the invoice number ' .
            'as reference.'
        ;

        $address =
            $faker->streetAddress . PHP_EOL .
            $faker->city . ', ' . $faker->stateAbbr . ' ' . $faker->postcode . PHP_EOL .
            'Phone: ' . $faker->phoneNumber . PHP_EOL .
            'Email: ' . $faker->safeEmail
        ;

        $paymentTerms_de =
            'Bitte überweisen Sie den Gesamtbetrag innerhalb von 14 Tagen nach Erhalt der Rechnung auf das unten genannte Konto. Verwenden Sie bitte als Betreff Ihrer Überweisung die Rechnungsnummer.' .
            PHP_EOL .
            PHP_EOL .
            'Ich bedanke mich für das entgegengebrachte Vertrauen. Gerne bin ich auch künftig für Sie da.' .
            PHP_EOL .
            PHP_EOL .
            'Mit freundlichen Grüßen,' .
            PHP_EOL .
            'Max Müller'
        ;

        // name, title, renderer, calculator, numberGenerator, company, vat, dueDays, address, paymentTerms
        return [
            ['Invoice (HTML)',            'Company name',    'default',          'default',  'default', $faker->company, 19, 30, $address, $paymentTerms],
            ['Freelancer (HTML, short)',  'Invoice',         'freelancer',       'short',    'default', $faker->company, 19, 14, $this->generateAddress($faker), $paymentTerms_de],
            ['Timesheet (HTML)',          'Timesheet',       'timesheet',        'default',  'default', $faker->company, 19, 7,  $this->generateAddress($faker), ''],
            ['Company invoice (DOCX)',    'Invoice',         'company',          'default',  'default', 'Kimai Inc.',    19, 14, $this->generateAddress($faker, true), $this->generatePaymentTerms($faker)],
            ['Export (CSV, user-group)',  'User-grouped',    'export',           'user',     'default', $faker->company, 7,  28, '', ''],
            ['Export (ODS)',              'Spreadsheet',     'open-spreadsheet', 'default',  'default', $faker->company, 19, 14, '', ''],
            ['Export (XLSX, user-group)', 'Spreadsheet',     'spreadsheet',      'user',     'default', $faker->company, 13, 10, '', ''],
        ];
    }

    protected function generatePaymentTerms(Generator $faker)
    {
        return
            'Acme Bank' . PHP_EOL .
            'Account: ' . $faker->bankAccountNumber . PHP_EOL .
            'IBAN: ' . $faker->iban('DE')
        ;
    }

    protected function generateAddress(Generator $faker, $lineBreaks = false)
    {
        if (!$lineBreaks) {
            return
                $faker->name . ' - ' .
                $faker->streetAddress . '-' .
                $faker->postcode . ' ' . $faker->city;
        }

        return
            'Kimai Inc.' . PHP_EOL .
            $faker->streetAddress . PHP_EOL .
            $faker->city . ', ' . $faker->stateAbbr . ' ' . $faker->postcode
        ;
    }
}
