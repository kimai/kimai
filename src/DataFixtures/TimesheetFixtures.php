<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetFixtures extends Fixture
{
    use FixturesTrait;

    const AMOUNT_TIMESHEET = 5000;      // timesheet entries total
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

        // by using array_pop we make sure that at least one activity has NO entry!
        array_pop($allActivity);

        for ($i = 0; $i <= self::AMOUNT_TIMESHEET; $i++) {
            $entry = $this->createTimesheetEntry(
                $allUser[rand(1, $amountUser)],
                $allActivity[array_rand($allActivity)],
                round($i / 2),
                true
            );

            $manager->persist($entry);
        }

        // by using array_pop we make sure that at least one user has NO running entry!
        array_pop($allUser);

        // create active recodinge for test user
        foreach ($allUser as $id => $user) {
            for ($i = 0; $i < rand(1, 4); $i++) {
                $entry = $this->createTimesheetEntry(
                    $user,
                    $allActivity[array_rand($allActivity)]
                );
                $manager->persist($entry);
            }
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
        $entry
            ->setActivity($activity)
            ->setDescription($this->getRandomPhrase())
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
        $allTimezones = \DateTimeZone::listIdentifiers();
        $amountTimezone = count($allTimezones);

        $allCustomer = $this->getCustomers();
        shuffle($allCustomer);
        $i = 1;

        foreach ($allCustomer as $customerName) {
            $visible = $i++ % 6 != 0;
            $entry = new Customer();
            $entry
                ->setCurrency($this->getRandomCurrency())
                ->setName($customerName . ($visible ? '' : '.'))
                ->setAddress($this->getRandomLocation())
                ->setComment($this->getRandomPhrase())
                ->setVisible($visible)
                ->setNumber('C0815-42-' . $i)
                ->setCountry('DE') // TODO randomize country ?
                ->setTimezone($allTimezones[rand(1, $amountTimezone)]);

            $manager->persist($entry);
        }
        $manager->flush();
    }

    private function loadProjects(ObjectManager $manager)
    {
        $allCustomer = $this->getAllCustomers($manager);

        foreach ($allCustomer as $id => $customer) {
            $projectForCustomer = rand(0, 7);
            for ($i = 1; $i <= $projectForCustomer; $i++) {
                $visible = $i % 5 != 0;
                $entry = new Project();

                $entry
                    ->setName($this->getRandomProject() . ($visible ? '' : '.'))
                    ->setBudget(rand(500, 100000))
                    ->setComment($this->getRandomPhrase())
                    ->setCustomer($customer)
                    ->setVisible($visible);

                $manager->persist($entry);
            }
        }
        $manager->flush();
    }

    private function loadActivities(ObjectManager $manager)
    {
        $allProject = $this->getAllProjects($manager);

        foreach ($allProject as $projectId => $project) {
            $activityCount = rand(0, 10);
            for ($i = 1; $i <= $activityCount; $i++) {
                $visible = $i % 4 != 0;
                $entry = new Activity();
                $entry
                    ->setName($this->getRandomActivity() . ($visible ? '' : '.'))
                    ->setProject($project)
                    ->setComment($this->getRandomPhrase())
                    ->setVisible($visible);

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
            'Designing',
            'Programming',
            'Testing',
            'Documentation',
            'Pause',
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
            'Skiing',
            'Eating',
            'Watching TV',
            'Talking',
            'Cooking',
            'Writing',
            'Reading',
            'Brainstroming',
            'Post Processing',
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
            'Princess Cat',
            'Software Upgrade',
            'Office Management',
            'Project X',
            'Customer Excellence',
            'Crazy Monkey',
            'Interface Design',
            'Human Ressources',
            'Book Release',
            'Studio Photography',
            'Professional Art',
            'Video Production',
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
            'Tokyo',
            'Berlin',
            'Sao Paulo',
            'Mexico City',
            'Moscow',
            'Sankt Petersburg',
            'Taiwan',
            'Perth',
            'Sydney',
            'Mumbai',
            'Lagos',
            'Karachi',
            'Shanghai',
            'Delhi',
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
            'Samsung',
            'Huawai',
            'Yandex',
            'Baidu',
            'Alphabet',
            'Amazon.com',
            'Berkshire Hathaway',
            'Facebook',
            'ExxonMobil',
            'Nestle',
            'Johnson & Johnson',
            'Alibaba',
            'General Electric',
            'Procter & Gamble',
            'Wal-Mart Stores',
            'Novartis',
            'Coca-Cola',
            'Wikipedia',
            'Walt Disney',
            'Merck',
            'Pfizer',
            "L'Oréal Group",
            "McDonald's",
            'China Petroleum & Chemical',
            'GlaxoSmithKline'
        ];
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
