<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
final class CustomerFixtures extends Fixture
{
    public const MIN_CUSTOMERS = 5;
    public const MAX_CUSTOMERS = 15;
    public const MIN_BUDGET = 0;
    public const MAX_BUDGET = 100000;
    public const MIN_TIME_BUDGET = 0;
    public const MAX_TIME_BUDGET = 10000000;
    public const MIN_GLOBAL_ACTIVITIES = 5;
    public const MAX_GLOBAL_ACTIVITIES = 30;
    public const MIN_PROJECTS_PER_CUSTOMER = 2;
    public const MAX_PROJECTS_PER_CUSTOMER = 25;
    public const MIN_ACTIVITIES_PER_PROJECT = 0;
    public const MAX_ACTIVITIES_PER_PROJECT = 25;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('at_AT');

        $amountCustomers = rand(self::MIN_CUSTOMERS, self::MAX_CUSTOMERS);
        for ($c = 1; $c <= $amountCustomers; $c++) {
            $visibleCustomer = 0 !== $c % 5;
            $customer = $this->createCustomer($faker, $visibleCustomer);
            $manager->persist($customer);

            $projectForCustomer = rand(self::MIN_PROJECTS_PER_CUSTOMER, self::MAX_PROJECTS_PER_CUSTOMER);
            for ($p = 1; $p <= $projectForCustomer; $p++) {
                $visibleProject = 0 !== $p % 7;
                $project = $this->createProject($faker, $customer, $visibleProject);
                $manager->persist($project);

                $activityForProject = rand(self::MIN_ACTIVITIES_PER_PROJECT, self::MAX_ACTIVITIES_PER_PROJECT);
                for ($a = 1; $a <= $activityForProject; $a++) {
                    $visibleActivity = 0 !== $a % 6;
                    $activity = $this->createActivity($faker, $project, $visibleActivity);
                    $manager->persist($activity);
                }
            }

            $manager->flush();
            $manager->clear();
        }

        $amountGlobalActivities = rand(self::MIN_GLOBAL_ACTIVITIES, self::MAX_GLOBAL_ACTIVITIES);
        for ($c = 1; $c <= $amountGlobalActivities; $c++) {
            $visibleActivity = 0 !== $c % 4;
            $activity = $this->createActivity($faker, null, $visibleActivity);
            $manager->persist($activity);
        }

        $manager->flush();
        $manager->clear();
    }

    private function createCustomer(Generator $faker, bool $visible): Customer
    {
        $entry = new Customer($faker->company());
        $entry->setCurrency($faker->currencyCode());
        $entry->setAddress($faker->address());
        $entry->setEmail($faker->safeEmail());
        $entry->setComment($faker->text());
        $entry->setNumber('C-' . $faker->ean8());
        $entry->setCountry($faker->countryCode());
        $entry->setTimezone($faker->timezone());
        $entry->setVisible($visible);
        $entry->setVatId($faker->creditCardNumber());

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    private function createProject(Generator $faker, Customer $customer, bool $visible): Project
    {
        $entry = new Project();

        /** @var string $name */
        $name = $faker->words(2, true);

        $entry->setName(ucfirst($name));
        $entry->setComment($faker->text());
        $entry->setCustomer($customer);
        $entry->setOrderNumber('P-' . $faker->ean8());
        $entry->setVisible($visible);

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    private function createActivity(Generator $faker, ?Project $project, bool $visible): Activity
    {
        /** @var string $name */
        $name = $faker->words(2, true);

        $entry = new Activity();
        $entry->setName(ucfirst($name));
        $entry->setProject($project);
        $entry->setComment($faker->text());
        $entry->setVisible($visible);

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }
}
