<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
final class CustomerFixtures extends Fixture
{
    /**
     * @var int
     */
    private $amount = 0;
    /**
     * @var bool
     */
    private $isVisible = null;
    /**
     * @var callable
     */
    private $callback;

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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        for ($i = 0; $i < $this->amount; $i++) {
            $visible = 0 != $i % 3;
            if (null !== $this->isVisible) {
                $visible = $this->isVisible;
            }
            $customer = new Customer();
            $customer
                ->setCurrency($faker->currencyCode)
                ->setName($faker->company . ($visible ? '' : ' (x)'))
                ->setAddress($faker->address)
                ->setComment($faker->text)
                ->setNumber('C-' . $faker->ean8)
                ->setCountry($faker->countryCode)
                ->setTimezone($faker->timezone)
                ->setVisible($visible)
            ;

            if (null !== $this->callback) {
                \call_user_func($this->callback, $customer);
            }
            $manager->persist($customer);
        }

        $manager->flush();
    }
}
