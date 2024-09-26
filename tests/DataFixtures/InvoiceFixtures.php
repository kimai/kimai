<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Invoice;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
class InvoiceFixtures implements TestFixture
{
    use FixturesTrait;

    private int $amount = 50;
    private array $status = [Invoice::STATUS_CANCELED, Invoice::STATUS_NEW, Invoice::STATUS_PAID, Invoice::STATUS_PENDING];

    public function setAmount(int $amount = 50): void
    {
        $this->amount = $amount;
    }

    public function setStatus(array $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Invoice[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        $faker = Factory::create();

        $customers = $this->getAllCustomers($manager);
        $users = $this->getAllUsers($manager);

        for ($i = 0; $i < $this->amount; $i++) {
            $total = $faker->randomFloat(2, 50, 5000);
            $vat = $faker->randomFloat(2, 0, 23) / 10;
            $tax = $total * $vat;

            $invoice = new Invoice();
            $invoice->setStatus($this->status[array_rand($this->status)]);
            $invoice->setTotal($total);
            $invoice->setVat($vat);
            $invoice->setTax($tax);
            $invoice->setCustomer($customers[array_rand($customers)]);

            $prefix = uniqid($i . '_') . '_';
            $invoice->setInvoiceNumber($prefix . $faker->randomNumber(3));
            $invoice->setFilename($prefix . $faker->randomNumber(3));
            $invoice->setCreatedAt($faker->dateTimeBetween('-1 year', 'now'));
            $invoice->setUser($users[array_rand($users)]);
            $invoice->setDueDays($faker->randomNumber(2));
            $invoice->setComment($faker->text(300));
            $invoice->setCurrency($faker->currencyCode());

            $manager->persist($invoice);
            $created[] = $invoice;
        }

        $manager->flush();

        return $created;
    }
}
