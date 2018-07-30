<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
class TimesheetFixtures extends Fixture
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var int
     */
    protected $amount = 0;

    /**
     * @var int
     */
    protected $running = 0;

    /**
     * @var string
     */
    protected $startDate = '2018-04-01';

    /**
     * @param string $date
     */
    public function setStartDate($date)
    {
        $this->startDate = $date;
    }

    /**
     * @param int $amount
     */
    public function setAmountRunning($amount)
    {
        $this->running = $amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $activities = $this->getAllActivities($manager);
        $faker = Factory::create();
        $user = $this->user;

        for ($i = 0; $i < $this->amount; $i++) {
            $entry = $this->createTimesheetEntry(
                $user,
                $activities[array_rand($activities)],
                ($i % 3 == 0 ? $faker->text : ''),
                $this->getDateTime($i)
            );

            $manager->persist($entry);
        }

        for ($i = 0; $i < $this->running; $i++) {
            $entry = $this->createTimesheetEntry(
                $user,
                $activities[array_rand($activities)],
                $faker->text,
                $this->getDateTime($i)
            );
            $manager->persist($entry);
        }

        $manager->flush();
    }

    /**
     * @param $i
     * @return bool|\DateTime
     */
    protected function getDateTime($i)
    {
        $start = \DateTime::createFromFormat('Y-m-d', $this->startDate);
        $start->modify("+ $i days");
        $start->modify('+ ' . rand(1, 172.800) . ' seconds'); // up to 2 days
        return $start;
    }

    /**
     * @param ObjectManager $manager
     * @return Activity[]
     */
    protected function getAllActivities(ObjectManager $manager)
    {
        $all = [];
        /* @var User[] $entries */
        $entries = $manager->getRepository(Activity::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param User $user
     * @param Activity $activity
     * @param $description
     * @param \DateTime $start
     * @param bool $setEndDate
     * @return Timesheet
     */
    private function createTimesheetEntry(User $user, Activity $activity, $description, \DateTime $start, $setEndDate = true)
    {
        $end = clone $start;
        $end = $end->modify('+ ' . (rand(1, 172800)) . ' seconds');

        $duration = $end->getTimestamp() - $start->getTimestamp();
        $rate = $user->getPreferenceValue(UserPreference::HOURLY_RATE);

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
}
