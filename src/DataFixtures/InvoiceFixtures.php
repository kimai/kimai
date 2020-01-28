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
        $faker = Factory::create('at_AT');

        foreach ($this->getInvoiceConfigs($faker) as $invoiceConfig) {
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
                ->setPaymentTerms($invoiceConfig[8])
                ->setVatId($faker->vat)
                ->setAddress($this->generateAddress($faker))
                ->setContact($this->generateContact($faker))
                ->setPaymentDetails($this->generatePaymentDetails($faker))
            ;

            $manager->persist($template);
            $manager->flush();
        }
    }

    private function getInvoiceConfigs(Generator $faker)
    {
        $paymentTerms =
            'I would like to thank you for your confidence and will gladly be there for you in the future.' .
            PHP_EOL .
            'Please transfer the total amount within 14 days to the given account and use the invoice number ' .
            'as reference.'
        ;

        $paymentTerms_alt =
            $faker->firstName . ', thank you very much. We really appreciate your business.' . PHP_EOL .
            'Please send payments before the due date. I would like to thank you for your confidence and will gladly be there for you in the future.'
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
            ['Invoice (HTML)',            'Company name',    'default',          'default',  'default', $faker->company, 19, 30, $paymentTerms],
            ['Freelancer (HTML, short)',  'Invoice',         'freelancer',       'short',    'default', $faker->company, 19, 14, $paymentTerms_de],
            ['Timesheet (HTML)',          'Timesheet',       'timesheet',        'default',  'default', $faker->company, 19, 7,  $paymentTerms_alt],
            ['Company invoice (DOCX)',    'Invoice',         'company',          'default',  'default', 'Kimai Inc.',    19, 14, $paymentTerms_alt],
        ];
    }

    protected function generatePaymentDetails(Generator $faker)
    {
        return
            'Acme Bank' . PHP_EOL .
            'Account: ' . $faker->bankAccountNumber . PHP_EOL .
            'IBAN: ' . $faker->iban('DE')
        ;
    }

    protected function generateContact(Generator $faker)
    {
        return
            'Phone: ' . $faker->phoneNumber . PHP_EOL .
            'Email: ' . $faker->safeEmail . PHP_EOL .
            'Web: www.' . $faker->domainName
        ;
    }

    protected function generateAddress(Generator $faker)
    {
        return
            $faker->streetAddress . PHP_EOL .
            $faker->city . ', ' . $faker->stateAbbr . ' ' . $faker->postcode
        ;
    }
}
