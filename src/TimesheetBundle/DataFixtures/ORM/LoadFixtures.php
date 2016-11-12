<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Entity\Timesheet;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\DataFixtures\ORM\LoadFixtures as AppBundleLoadFixtures;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests. Execute this command to load the data:
 *
 *   $ php bin/console doctrine:fixtures:load
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class LoadFixtures extends AppBundleLoadFixtures
{
    const AMOUNT_ACTIVITIES = 10;       // maximum activites per project
    const AMOUNT_TIMESHEET = 1000;      // timesheet entries total
    const AMOUNT_PROJECTS = 20;         // projects entries total
    const AMOUNT_CUSTOMER = 10;         // customer entries total
    const RATE_MIN = 10;                // minimum rate for one hour
    const RATE_MAX = 80;                // maximum rate for one hour

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
        $amountUser = count($allUser);

        $allActivity = $this->getAllActivities($manager);

        for ($i = 0; $i <= self::AMOUNT_TIMESHEET; $i++) {

            $startDay = round($i / 2);
            $start = new \DateTime();
            $start = $start->modify('- ' . (rand(1, $startDay)) . ' days');
            $start = $start->modify('- ' . (rand(1, 86400)) . ' seconds');

            $end = clone $start;
            $end = $end->modify('+ '.(rand(1, 43200)).' seconds');

            //$duration = $end->modify('- ' . $start->getTimestamp() . ' seconds')->getTimestamp();
            $duration = $end->getTimestamp() - $start->getTimestamp();
            $rate = rand(self::RATE_MIN, self::RATE_MAX);

            $entry = new Timesheet();
            $entry->setActivity($allActivity[array_rand($allActivity)]);
            $entry->setDescription($this->getRandomPhrase());
            $entry->setUser($allUser[rand(1, $amountUser)]);
            $entry->setRate(round(($duration / 3600) * $rate));
            $entry->setBegin($start);

            // leave one running time entry
            if ($i < self::AMOUNT_TIMESHEET) {
                $entry->setEnd($end);
                $entry->setDuration($duration);
            }

            $manager->persist($entry);
        }
        $manager->flush();
    }

    private function loadCustomers(ObjectManager $manager)
    {
        $allTimezones = \DateTimeZone::listIdentifiers();
        $amountTimezone = count($allTimezones);

        for ($i = 0; $i <= self::AMOUNT_CUSTOMER; $i++) {

            $entry = new Customer();
            $entry->setName($this->getRandomCustomer());
            $entry->setCity($this->getRandomLocation());
            $entry->setComment($this->getRandomPhrase());
            $entry->setVisible($i % 3 != 0);
            $entry->setTimezone($allTimezones[rand(1, $amountTimezone)]);

            $manager->persist($entry);
        }
        $manager->flush();
    }

    private function loadProjects(ObjectManager $manager)
    {
        $allCustomer = $this->getAllCustomers($manager);
        $amountCustomer = count($allCustomer);

        for ($i = 0; $i <= self::AMOUNT_PROJECTS; $i++) {

            $entry = new Project();
            $entry->setName($this->getRandomProject());
            $entry->setBudget(rand(1000, 100000));
            $entry->setComment($this->getRandomPhrase());
            $entry->setCustomer($allCustomer[rand(1, $amountCustomer)]);
            $entry->setVisible($i % 3 != 0);

            $manager->persist($entry);
        }
        $manager->flush();
    }

    private function loadActivities(ObjectManager $manager)
    {
        $allProject = $this->getAllProjects($manager);

        foreach ($allProject as $projectId => $project) {
            $activityCount = rand(1, self::AMOUNT_ACTIVITIES);
            for ($i = 0; $i < $activityCount; $i++) {
                $entry = new Activity();
                $entry->setProject($project);
                $entry->setName($this->getRandomActivity());
                $entry->setComment($this->getRandomPhrase());
                $entry->setVisible($i % 3 != 0);

                $manager->persist($entry);
            }
        }
        $manager->flush();
    }

    private function getActivities()
    {
        return [
            'Design',
            'Programming',
            'Testing',
            'Documentation',
            'Pause',
            'Internal',
            'Research',
            'Meeting',
        ];
    }

    private function getRandomActivity()
    {
        $all = $this->getActivities();
        return $all[array_rand($all)];
    }

    private function getProjects()
    {
        return [
            'FooBar',
            'Relaunch',
            'Refactoring',
            'Test Automatisation',
            'Website redesign',
            'Services',
        ];
    }

    private function getRandomProject()
    {
        $all = $this->getProjects();
        return $all[array_rand($all)];
    }

    private function getLocations()
    {
        return [
            'Köln',
            'München',
            'New York',
            'Buenos Aires',
            'Hawai',
            'Amsterdam',
            'London',
            'San Francisco',
            'Tokio',
            'Berlin',
            'Sao Paulo',
            'Mexico City',
        ];
    }

    private function getRandomLocation()
    {
        $all = $this->getLocations();
        return $all[array_rand($all)];
    }

    private function getCustomers()
    {
        return [
            'Acme University',
            'Snake Oil',
            'Apple',
            'Microsoft',
            'Google',
            'Oracle',
            'Yahoo',
            'Twitter',
            'Zend',
            'SensioLabs',
        ];
    }

    private function getRandomCustomer()
    {
        $all = $this->getCustomers();
        return $all[array_rand($all)];
    }
}
