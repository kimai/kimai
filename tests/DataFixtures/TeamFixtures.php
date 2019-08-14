<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
class TeamFixtures extends Fixture
{
    /**
     * @var int
     */
    protected $amount = 0;
    /**
     * @var bool
     */
    protected $addCustomer = true;

    public function setAddCustomer(bool $useCustomer)
    {
        $this->addCustomer = $useCustomer;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): TeamFixtures
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        $user = $this->getAllUsers($manager);
        $customer = $this->getAllCustomers($manager);

        for ($i = 0; $i < $this->amount; $i++) {
            $lead = $user[array_rand($user)];
            $entity = new Team();
            $entity
                ->setName($faker->name)
                ->setTeamLead($lead)
            ;
            $entity->addUser($lead);
            $entity->addUser($user[array_rand($user)]);

            if ($this->addCustomer) {
                $entity->addCustomer($customer[array_rand($customer)]);
            }

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Customer[]
     */
    protected function getAllCustomers(ObjectManager $manager)
    {
        $all = [];
        /* @var Customer[] $entries */
        $entries = $manager->getRepository(Customer::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param ObjectManager $manager
     * @return User[]
     */
    protected function getAllUsers(ObjectManager $manager)
    {
        $all = [];
        /* @var User[] $entries */
        $entries = $manager->getRepository(User::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }
}
