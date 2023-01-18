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
use Doctrine\Persistence\ObjectManager;
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
final class InvoiceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        foreach ($this->getInvoiceConfigs($faker) as $invoiceConfig) {
            // name, title, renderer, calculator, numberGenerator, company, vat, dueDays, address, paymentTerms
            $template = new InvoiceTemplate();
            $template->setName($invoiceConfig[0]);
            $template->setTitle($invoiceConfig[1]);
            $template->setRenderer($invoiceConfig[2]);
            $template->setCalculator($invoiceConfig[3]);
            $template->setNumberGenerator($invoiceConfig[4]);
            $template->setCompany($invoiceConfig[5]);
            $template->setVat($invoiceConfig[6]);
            $template->setDueDays($invoiceConfig[7]);
            $template->setPaymentTerms($invoiceConfig[8]);
            $template->setLanguage('en');
            $template->setAddress($this->generateAddress($faker));
            $template->setContact($this->generateContact($faker));
            $template->setPaymentDetails($this->generatePaymentDetails($faker));
            $template->setVatId($faker->creditCardNumber());

            $manager->persist($template);
            $manager->flush();
        }
    }

    /**
     * @param Generator $faker
     * @return array
     */
    private function getInvoiceConfigs(Generator $faker): array
    {
        $paymentTerms =
            'I would like to thank you for your confidence and will gladly be there for you in the future.' .
            PHP_EOL .
            'Please transfer the total amount within 14 days to the given account and use the invoice number ' .
            'as reference.'
        ;

        $paymentTerms_alt =
            $faker->firstName() . ', thank you very much. We really appreciate your business.' . PHP_EOL .
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
            ['Default (PDF)',             'Invoice',         'default',         'default',  'default', $faker->company(), 16, 10, $paymentTerms],
            ['Invoice (HTML)',            'Company name',    'invoice',         'default',  'default', $faker->company(), 19, 30, $paymentTerms],
            ['Single service date (PDF)', 'Invoice',         'service-date',    'short',    'default', $faker->company(), 19, 14, $paymentTerms_de],
            ['Timesheet (HTML)',          'Timesheet',       'timesheet',       'default',  'default', $faker->company(), 19, 7,  $paymentTerms_alt],
        ];
    }

    private function generatePaymentDetails(Generator $faker): string
    {
        return
            'Acme Bank' . PHP_EOL .
            'BIC: ' . $faker->swiftBicNumber() . PHP_EOL .
            'IBAN: ' . $faker->iban('DE')
        ;
    }

    private function generateContact(Generator $faker): string
    {
        return
            'Phone: ' . $faker->phoneNumber() . PHP_EOL .
            'Email: ' . $faker->safeEmail() . PHP_EOL .
            'Web: www.' . $faker->domainName()
        ;
    }

    private function generateAddress(Generator $faker): string
    {
        return
            $faker->streetAddress() . PHP_EOL .
            $faker->postcode() . ' ' . $faker->city() . ', ' . $faker->country()
        ;
    }
}
