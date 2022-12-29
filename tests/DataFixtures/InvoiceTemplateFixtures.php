<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\InvoiceTemplate;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
class InvoiceTemplateFixtures implements TestFixture
{
    /**
     * @param ObjectManager $manager
     * @return InvoiceTemplate[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        $faker = Factory::create();

        $template = new InvoiceTemplate();
        $template->setName('Invoice');
        $template->setTitle('Your company name');
        $template->setCompany($faker->company());
        $template->setVat(19);
        $template->setDueDays(14);
        $template->setPaymentTerms(
            'I would like to thank you for your confidence and will gladly be there for you in the future.' .
            PHP_EOL .
            'Please transfer the total amount within 14 days to the given account and use the invoice number ' .
            'as reference.'
        );
        $template->setAddress(
            $faker->streetAddress() . PHP_EOL .
            $faker->city() . ' ' . $faker->postcode() . ', ' . $faker->country() . PHP_EOL .
            'Phone: ' . $faker->phoneNumber() . PHP_EOL .
            'Email: ' . $faker->safeEmail()
        );
        $template->setLanguage('en');
        $template->setRenderer('invoice');

        $manager->persist($template);
        $manager->flush();

        $created[] = $template;

        return $created;
    }
}
