<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Customer;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
final class CustomerFixtures implements TestFixture
{
    private int $amount = 0;
    private ?bool $isVisible = null;
    /**
     * @var callable
     */
    private $callback;

    public function __construct(int $amount = 0)
    {
        $this->amount = $amount;
    }

    /**
     * Will be called prior to persisting the object.
     *
     * @param callable $callback
     * @return CustomerFixtures
     */
    public function setCallback(callable $callback): CustomerFixtures
    {
        $this->callback = $callback;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): CustomerFixtures
    {
        $this->amount = $amount;

        return $this;
    }

    public function setIsVisible(bool $visible): CustomerFixtures
    {
        $this->isVisible = $visible;

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @return Customer[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        $faker = Factory::create();

        for ($i = 0; $i < $this->amount; $i++) {
            $visible = 0 != $i % 3;
            if (null !== $this->isVisible) {
                $visible = $this->isVisible;
            }
            $customer = new Customer($faker->company() . ($visible ? '' : ' (x)'));
            $customer->setCurrency($faker->currencyCode());
            $customer->setAddress($faker->address());
            $customer->setEmail($faker->safeEmail());
            $customer->setComment($faker->text());
            $customer->setNumber('C-' . $faker->ean8());
            $customer->setCountry($faker->countryCode());
            $customer->setTimezone($faker->timezone());
            $customer->setVisible($visible);

            if (null !== $this->callback) {
                \call_user_func($this->callback, $customer);
            }
            $manager->persist($customer);
            $created[] = $customer;
        }

        $manager->flush();

        return $created;
    }
}
