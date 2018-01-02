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
use Symfony\Component\Intl\Intl;
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
            $entry = $this->createTimesheetEntry(
                $allUser[rand(1, $amountUser)],
                $allActivity[array_rand($allActivity)],
                round($i / 2),
                true
            );

            $manager->persist($entry);
        }

        // leave one running time entry for each user
        for ($i = 1; $i <= $amountUser; $i++) {
            $entry = $this->createTimesheetEntry(
                $allUser[$i],
                $allActivity[array_rand($allActivity)]
            );

            $manager->persist($entry);
        }

        $manager->flush();
    }

    private function createTimesheetEntry(User $user, Activity $activity, $startDay = 0, $setEndDate = false)
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
        $rate = rand(self::RATE_MIN, self::RATE_MAX);

        $entry = new Timesheet();
        $entry->setActivity($activity);
        $entry->setDescription($this->getRandomPhrase());
        $entry->setUser($user);
        $entry->setRate(round(($duration / 3600) * $rate));
        $entry->setBegin($start);

        if ($setEndDate) {
            $entry->setEnd($end);
            $entry->setDuration($duration);
        }

        return $entry;
    }

    private function loadCustomers(ObjectManager $manager)
    {
        $allTimezones = \DateTimeZone::listIdentifiers();
        $amountTimezone = count($allTimezones);

        $allCustomer = $this->getCustomers();
        $amountCustomer = count($allCustomer);

        for ($i = 0; $i < $amountCustomer; $i++) {
            $entry = new Customer();
            $entry->setName($allCustomer[$i]);
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

        for ($i = 0; $i < $amountCustomer * 2; $i++) {

            $entry = new Project();
            $entry->setName($this->getRandomProject());
            $entry->setCurrency($this->getRandomCurrency());
            $entry->setBudget(rand(1000, 100000));
            $entry->setComment($this->getRandomPhrase());
            $entry->setCustomer($allCustomer[($i % $amountCustomer) + 1]);
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

    /**
     * @return string[]
     */
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
            'Hosting',
            'Relaunch',
            'Support',
            'Refactoring',
            'Interview',
            'Administration',
            'DevOps',
            'Management',
            'Setup',
            'Planning',
        ];
    }

    /**
     * @return string
     */
    private function getRandomActivity()
    {
        $all = $this->getActivities();
        return $all[array_rand($all)];
    }

    /**
     * @return string[]
     */
    private function getProjects()
    {
        return [
            'User Experience',
            'Database Migration',
            'Test Automatisation',
            'Website Redesign',
            'API Development',
            'Hosting & Server',
            'Customer Relations',
            'Infrastructure',
            'Software Upgrade',
            'Office Managemenr',
        ];
    }

    /**
     * @return string
     */
    private function getRandomProject()
    {
        $all = $this->getProjects();
        return $all[array_rand($all)];
    }

    /**
     * @return string[]
     */
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

    /**
     * @return string
     */
    private function getRandomLocation()
    {
        $all = $this->getLocations();
        return $all[array_rand($all)];
    }

    /**
     * @return string[]
     */
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

    /**
     * @return string
     */
    private function getRandomCustomer()
    {
        $all = $this->getCustomers();
        return $all[array_rand($all)];
    }

    /**
     * @return string[]
     */
    private function getCurrencies()
    {
        return [
            'EUR',
            'GBP',
            'USD',
            'RUB',
            'JPY',
            'CNY',
            'INR'
        ];
    }

    /**
     * @return string
     */
    private function getRandomCurrency()
    {
        $all = $this->getCurrencies();
        return $all[array_rand($all)];
    }
}
