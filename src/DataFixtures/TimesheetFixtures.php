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
use App\Entity\Timesheet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * bin/console doctrine:fixtures:load
 */
class TimesheetFixtures extends Fixture
{
    public const MIN_CUSTOMERS = 200;
    public const MAX_CUSTOMERS = 200;
    public const MIN_PROJECTS_PER_CUSTOMER = 10;
    public const MAX_PROJECTS_PER_CUSTOMER = 100;
    public const MIN_ACTIVITIES_PER_PROJECT = 0;
    public const MAX_ACTIVITIES_PER_PROJECT = 25;
    public const MIN_TIMESHEETS_PER_USER = 5;
    public const MAX_TIMESHEETS_PER_USER = 100;
    public const MAX_TIMESHEETS_TOTAL = 50000;
    public const MIN_RUNNING_TIMESHEETS_PER_USER = 0;
    public const MAX_RUNNING_TIMESHEETS_PER_USER = 4;
    public const MIN_RATE = 30;
    public const MAX_RATE = 120;
    public const MIN_BUDGET = 0;
    public const MAX_BUDGET = 100000;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadCustomers($manager);
        $this->loadProjects($manager);
        $this->loadActivities($manager);
        $this->loadTimesheet($manager);
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
     * @return Project[]
     */
    protected function getAllProjects(ObjectManager $manager)
    {
        $all = [];
        /* @var Project[] $entries */
        $entries = $manager->getRepository(Project::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param ObjectManager $manager
     * @return Activity[]
     */
    protected function getAllActivities(ObjectManager $manager)
    {
        $all = [];
        /* @var Activity[] $entries */
        $entries = $manager->getRepository(Activity::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    private function loadTimesheet(ObjectManager $manager)
    {
        $allUser = $this->getAllUsers($manager);
        $allActivity = $this->getAllActivities($manager);

        $faker = Factory::create();

        // by using array_pop we make sure that at least one activity has NO entry!
        array_pop($allActivity);

        foreach ($allUser as $user) {
            // random amount of timesheet entries for every user
            $amountEntries = rand(self::MIN_TIMESHEETS_PER_USER, self::MAX_TIMESHEETS_PER_USER);
            for ($i = 0; $i < $amountEntries; $i++) {
                if ($i > self::MAX_TIMESHEETS_TOTAL) {
                    break;
                }
                $entry = $this->createTimesheetEntry(
                    $user,
                    $allActivity[array_rand($allActivity)],
                    ($i % 3 == 0 ? $faker->text : ''),
                    round($i / 2),
                    true
                );

                $manager->persist($entry);
                if ($i % 9 == 0) {
                    $manager->flush();
                }
            }

            // create active recordings for test user
            $activeEntries = rand(self::MIN_RUNNING_TIMESHEETS_PER_USER, self::MAX_RUNNING_TIMESHEETS_PER_USER);
            for ($i = 0; $i < $activeEntries; $i++) {
                $entry = $this->createTimesheetEntry(
                    $user,
                    $allActivity[array_rand($allActivity)],
                    $faker->text
                );
                $manager->persist($entry);
            }
            $manager->flush();
        }
    }

    private function createTimesheetEntry(User $user, Activity $activity, $description, $startDay = 0, $setEndDate = false)
    {
        $start = new \DateTime();
        if ($startDay > 0) {
            $start = $start->modify('- ' . (rand(1, $startDay)) . ' days');
        }
        $start = $start->modify('- ' . (rand(1, 86400)) . ' seconds');

        $end = clone $start;
        $end = $end->modify('+ ' . (rand(1, 43200)) . ' seconds');

        //$duration = $end->modify('- ' . $start->getTimestamp() . ' seconds')->getTimestamp();
        $duration = $end->getTimestamp() - $start->getTimestamp();
        $rate = rand(self::MIN_RATE, self::MAX_RATE);

        $entry = new Timesheet();
        $entry
            ->setActivity($activity)
            ->setDescription($description)
            ->setUser($user)
            ->setRate(round(($duration / 3600) * $rate))
            ->setBegin($start);

        if ($setEndDate) {
            $entry
                ->setEnd($end)
                ->setDuration($duration);
        }

        return $entry;
    }

    private function loadCustomers(ObjectManager $manager)
    {
        $faker = Factory::create();

        $amountCustomers = rand(self::MIN_CUSTOMERS, self::MAX_CUSTOMERS);
        for ($i = 0; $i < $amountCustomers; $i++) {
            $visible = $faker->boolean;
            $entry = new Customer();
            $entry
                ->setCurrency($faker->currencyCode)
                ->setName($faker->company . ($visible ? '' : ' (x)'))
                ->setAddress($faker->address)
                ->setComment($faker->text)
                ->setVisible($visible)
                ->setNumber('C0815-42-' . $i)
                ->setCountry($faker->countryCode)
                ->setTimezone($faker->timezone);

            $manager->persist($entry);

            if ($i % 9 == 0) {
                $manager->flush();
            }
        }
        $manager->flush();
    }

    private function loadProjects(ObjectManager $manager)
    {
        $allCustomer = $this->getAllCustomers($manager);

        $faker = Factory::create();

        foreach ($allCustomer as $id => $customer) {
            $projectForCustomer = rand(self::MIN_PROJECTS_PER_CUSTOMER, self::MAX_PROJECTS_PER_CUSTOMER);
            for ($i = 1; $i <= $projectForCustomer; $i++) {
                $visible = 0 != $i % 5;
                $entry = new Project();

                $entry
                    ->setName($faker->catchPhrase . ($visible ? '' : ' (x)'))
                    ->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET))
                    ->setComment($faker->text)
                    ->setCustomer($customer)
                    ->setVisible($faker->boolean);

                $manager->persist($entry);

                if ($i % 9 == 0) {
                    $manager->flush();
                }
            }
            $manager->flush();
        }
    }

    private function loadActivities(ObjectManager $manager)
    {
        $allProject = $this->getAllProjects($manager);

        $faker = Factory::create();

        foreach ($allProject as $projectId => $project) {
            $activityCount = rand(self::MIN_ACTIVITIES_PER_PROJECT, self::MAX_ACTIVITIES_PER_PROJECT);
            for ($i = 1; $i <= $activityCount; $i++) {
                $visible = 0 != $i % 4;
                $entry = new Activity();
                $entry
                    ->setName($faker->bs . ($visible ? '' : ' (x)'))
                    ->setProject($project)
                    ->setComment($faker->text)
                    ->setVisible($faker->boolean);

                $manager->persist($entry);

                if ($i % 9 == 0) {
                    $manager->flush();
                }
            }
            $manager->flush();
        }
    }
}
