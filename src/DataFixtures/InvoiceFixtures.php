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

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 */
class InvoiceFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadInvoiceTemplate($manager);
        $this->loadFreelancerTemplate($manager);
        $this->loadTimesheetTemplate($manager);
        $this->loadDocxTemplate($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadDocxTemplate(ObjectManager $manager)
    {
        $faker = Factory::create();

        $template = new InvoiceTemplate();
        $template
            ->setName('Company invoice (docx)')
            ->setTitle('Invoice')
            ->setCompany('Kimai Inc.')
            ->setVat(19)
            ->setDueDays(14)
            ->setRenderer('company')
            ->setCalculator('default')
            ->setNumberGenerator('default')
            ->setPaymentTerms(
                'Acme Bank' . PHP_EOL .
                'Account: '.$faker->bankAccountNumber . PHP_EOL .
                'IBAN: ' . $faker->iban('DE')
            )
            ->setAddress(
                'Kimai Inc.' . PHP_EOL .
                $faker->streetAddress . PHP_EOL .
                $faker->city . ', ' . $faker->stateAbbr . ' ' . $faker->postcode
            )
        ;

        $manager->persist($template);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadInvoiceTemplate(ObjectManager $manager)
    {
        $faker = Factory::create();

        $template = new InvoiceTemplate();
        $template
            ->setName('Invoice')
            ->setTitle('Your company name')
            ->setCompany($faker->company)
            ->setVat(19)
            ->setDueDays(30)
            ->setRenderer('default')
            ->setCalculator('default')
            ->setNumberGenerator('default')
            ->setPaymentTerms(
                'I would like to thank you for your confidence and will gladly be there for you in the future.' .
                PHP_EOL .
                'Please transfer the total amount within 14 days to the given account and use the invoice number ' .
                'as reference.'
            )
            ->setAddress(
                $faker->streetAddress . PHP_EOL .
                $faker->city . ', ' . $faker->stateAbbr . ' ' . $faker->postcode . PHP_EOL .
                'Phone: ' . $faker->phoneNumber . PHP_EOL .
                'Email: ' . $faker->safeEmail
            )
        ;

        $manager->persist($template);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadFreelancerTemplate(ObjectManager $manager)
    {
        $faker = Factory::create();

        $template = new InvoiceTemplate();
        $template
            ->setName('Freelancer')
            ->setTitle('Rechnung')
            ->setCompany($faker->company)
            ->setVat(19)
            ->setDueDays(14)
            ->setRenderer('freelancer')
            ->setCalculator('short')
            ->setNumberGenerator('default')
            ->setPaymentTerms(
                'Bitte überweisen Sie den Gesamtbetrag innerhalb von 14 Tagen nach Erhalt der Rechnung auf das unten genannte Konto. Verwenden Sie bitte als Betreff Ihrer Überweisung die Rechnungsnummer.' .
                PHP_EOL .
                PHP_EOL .
                'Ich bedanke mich für das entgegengebrachte Vertrauen. Gerne bin ich auch künftig für Sie da.' .
                PHP_EOL .
                PHP_EOL .
                'Mit freundlichen Grüßen,' .
                PHP_EOL .
                'Max Müller'
            )
            ->setAddress(
                $faker->name . ' - ' . $faker->streetAddress . '-' . $faker->postcode . ' ' . $faker->city
            )
        ;

        $manager->persist($template);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadTimesheetTemplate(ObjectManager $manager)
    {
        $faker = Factory::create();

        $template = new InvoiceTemplate();
        $template
            ->setName('Timesheet')
            ->setTitle('Stundenzettel')
            ->setCompany($faker->company)
            ->setVat(19)
            ->setDueDays(14)
            ->setRenderer('timesheet')
            ->setCalculator('default')
            ->setNumberGenerator('default')
            ->setPaymentTerms('')
            ->setAddress(
                $faker->name . ' - ' . $faker->streetAddress . '-' . $faker->postcode . ' ' . $faker->city
            )
        ;

        $manager->persist($template);
        $manager->flush();
    }
}
