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
class CustomerFixtures extends Fixture
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

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('at_AT');

        $amountCustomers = rand(self::MIN_CUSTOMERS, self::MAX_CUSTOMERS);
        for ($c = 1; $c <= $amountCustomers; $c++) {
            $visibleCustomer = 0 != $c % 5;
            $customer = $this->createCustomer($faker, $visibleCustomer);
            $manager->persist($customer);

            $projectForCustomer = rand(self::MIN_PROJECTS_PER_CUSTOMER, self::MAX_PROJECTS_PER_CUSTOMER);
            for ($p = 1; $p <= $projectForCustomer; $p++) {
                $visibleProject = 0 != $p % 7;
                $project = $this->createProject($faker, $customer, $visibleProject);
                $manager->persist($project);

                $activityForProject = rand(self::MIN_ACTIVITIES_PER_PROJECT, self::MAX_ACTIVITIES_PER_PROJECT);
                for ($a = 1; $a <= $activityForProject; $a++) {
                    $visibleActivity = 0 != $a % 6;
                    $activity = $this->createActivity($faker, $project, $visibleActivity);
                    $manager->persist($activity);
                }
            }

            $manager->flush();
            $manager->clear();
        }

        $amountGlobalActivities = rand(self::MIN_GLOBAL_ACTIVITIES, self::MAX_GLOBAL_ACTIVITIES);
        for ($c = 1; $c <= $amountGlobalActivities; $c++) {
            $visibleActivity = 0 != $c % 4;
            $activity = $this->createActivity($faker, null, $visibleActivity);
            $manager->persist($activity);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param Generator $faker
     * @param bool $visible
     * @return Customer
     */
    private function createCustomer(Generator $faker, $visible)
    {
        $entry = new Customer();
        $entry
            ->setCurrency($faker->currencyCode)
            ->setName($faker->company)
            ->setAddress($faker->address)
            ->setComment($faker->text)
            ->setNumber('C-' . $faker->ean8)
            ->setCountry($faker->countryCode)
            ->setTimezone($faker->timezone)
            ->setVisible($visible)
            ->setVatId($faker->vat)
        ;

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    /**
     * @param Generator $faker
     * @param Customer $customer
     * @param bool $visible
     * @return Project
     */
    private function createProject(Generator $faker, Customer $customer, $visible)
    {
        $entry = new Project();

        $entry
            ->setName($faker->catchPhrase)
            ->setComment($faker->text)
            ->setCustomer($customer)
            ->setOrderNumber('P-' . $faker->ean8)
            ->setVisible($visible)
        ;

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    /**
     * @param Generator $faker
     * @param Project|null $project
     * @param bool $visible
     * @return Activity
     */
    private function createActivity(Generator $faker, ?Project $project, $visible)
    {
        $entry = new Activity();
        $entry
            ->setName($faker->bs)
            ->setProject($project)
            ->setComment($faker->text)
            ->setVisible($visible)
        ;

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }
}
