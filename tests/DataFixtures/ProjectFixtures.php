<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Customer;
use App\Entity\Project;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
final class ProjectFixtures implements TestFixture
{
    private int $amount = 0;
    private ?bool $isVisible = null;
    /**
     * @var callable
     */
    private $callback;
    /**
     * @var Customer[]
     */
    private array $customers = [];

    public function __construct(int $amount = 0)
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): ProjectFixtures
    {
        $this->amount = $amount;

        return $this;
    }

    public function setIsVisible(bool $visible): ProjectFixtures
    {
        $this->isVisible = $visible;

        return $this;
    }

    /**
     * @param Customer[] $customers
     * @return ProjectFixtures
     */
    public function setCustomers(array $customers): ProjectFixtures
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * Will be called prior to persisting the object.
     *
     * @param callable $callback
     * @return ProjectFixtures
     */
    public function setCallback(callable $callback): ProjectFixtures
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @return Project[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];
        $customers = $this->customers;
        if (empty($customers)) {
            $customers = $this->getAllCustomers($manager);
        }
        $faker = Factory::create();

        for ($i = 0; $i < $this->amount; $i++) {
            $visible = 0 != $i % 3;
            if (null !== $this->isVisible) {
                $visible = $this->isVisible;
            }
            $project = new Project();
            $project->setName($faker->company() . ($visible ? '' : ' (x)'));
            $project->setBudget(rand(0, 10000));
            $project->setComment($faker->text());
            $project->setCustomer($customers[array_rand($customers)]);
            $project->setVisible($visible);

            if (null !== $this->callback) {
                \call_user_func($this->callback, $project);
            }
            $manager->persist($project);
            $created[] = $project;
        }

        $manager->flush();

        return $created;
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Customer>
     */
    private function getAllCustomers(ObjectManager $manager): array
    {
        $all = [];
        /** @var Customer[] $entries */
        $entries = $manager->getRepository(Customer::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }
}
