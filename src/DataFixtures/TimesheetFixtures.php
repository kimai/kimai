<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Timesheet\Util;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
class TimesheetFixtures extends Fixture implements DependentFixtureInterface
{
    public const MIN_TIMESHEETS_PER_USER = 50;
    public const MAX_TIMESHEETS_PER_USER = 500;
    public const MAX_TIMESHEETS_TOTAL = 5000;
    public const MAX_RUNNING_TIMESHEETS_PER_USER = 1;
    public const TIMERANGE_DAYS = 1095; // 3 years
    public const TIMERANGE_RUNNING = 1047; // in minutes = 17:45 hours
    public const MIN_MINUTES_PER_ENTRY = 15;
    public const MAX_MINUTES_PER_ENTRY = 840; // 14h
    public const MAX_TAG_PER_ENTRY = 3;
    public const MAX_DESCRIPTION_LENGTH = 500;

    public const BATCH_SIZE = 100;

    /**
     * @return class-string[]
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            CustomerFixtures::class,
            TagFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $allUser = $this->getAllUsers($manager);
        $activities = $this->getAllActivities($manager);
        $projects = $this->getAllProjects($manager);
        $allTags = $this->getAllTags($manager);

        $faker = Factory::create();

        // by using array_pop we make sure that at least one activity has NO entry!
        array_pop($activities);

        $all = 0;

        foreach ($allUser as $user) {
            // random amount of timesheet entries for every user
            $timesheetForUser = rand(self::MIN_TIMESHEETS_PER_USER, self::MAX_TIMESHEETS_PER_USER);
            for ($i = 1; $i <= $timesheetForUser; $i++) {
                if ($all > self::MAX_TIMESHEETS_TOTAL && $i > self::MIN_TIMESHEETS_PER_USER) {
                    break;
                }

                $description = null;
                if ($i % 3 === 0) {
                    $description = $faker->realText($faker->numberBetween(10, self::MAX_DESCRIPTION_LENGTH));
                } elseif ($i % 7 === 0) {
                    $description = substr($faker->text, 0, self::MAX_DESCRIPTION_LENGTH);
                }

                $entry = $this->createTimesheetEntry(
                    $user,
                    $activities[array_rand($activities)],
                    $projects[array_rand($projects)],
                    $description,
                    true
                );

                $all++;

                $manager->persist($entry);

                if ($i % self::BATCH_SIZE === 0) {
                    $manager->flush();
                    $manager->clear(Timesheet::class);
                }
            }

            // create active recordings for test user
            $activeEntries = rand(0, self::MAX_RUNNING_TIMESHEETS_PER_USER);
            for ($i = 0; $i < $activeEntries; $i++) {
                $entry = $this->createTimesheetEntry(
                    $user,
                    $activities[array_rand($activities)],
                    $projects[array_rand($projects)],
                    null,
                    false
                );
                $manager->persist($entry);
            }

            $manager->flush();
            $manager->clear(Timesheet::class);
        }
        $manager->flush();

        $entries = $manager->getRepository(Timesheet::class)->findAll();
        foreach ($entries as $temp) {
            $tagAmount = rand(0, self::MAX_TAG_PER_ENTRY);
            for ($iTag = 0; $iTag < $tagAmount; $iTag++) {
                $tagId = rand(1, TagFixtures::MAX_TAGS);
                if (isset($allTags[$tagId])) {
                    $temp->addTag($allTags[$tagId]);
                }
            }
        }

        $manager->flush();
        $manager->clear(Timesheet::class);
        $manager->clear(Tag::class);
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Tag>
     */
    protected function getAllTags(ObjectManager $manager): array
    {
        $all = [];
        /** @var Tag[] $entries */
        $entries = $manager->getRepository(Tag::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, User>
     */
    protected function getAllUsers(ObjectManager $manager): array
    {
        $all = [];
        /** @var User[] $entries */
        $entries = $manager->getRepository(User::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Project>
     */
    protected function getAllProjects(ObjectManager $manager): array
    {
        $all = [];
        /** @var Project[] $entries */
        $entries = $manager->getRepository(Project::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Activity>
     */
    protected function getAllActivities(ObjectManager $manager): array
    {
        $all = [];
        /** @var Activity[] $entries */
        $entries = $manager->getRepository(Activity::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    private function createTimesheetEntry(User $user, Activity $activity, Project $project, $description, $setEndDate = true)
    {
        $start = new \DateTime();
        $start = $start->modify('- ' . (rand(1, self::TIMERANGE_DAYS)) . ' days');
        $start = $start->modify('- ' . (rand(1, 86400)) . ' seconds');
        $start->setTimezone(new \DateTimeZone($user->getPreferenceValue(UserPreference::TIMEZONE, date_default_timezone_get())));

        $entry = new Timesheet();
        $entry
            ->setActivity($activity)
            ->setProject($activity->getProject() ?? $project)
            ->setDescription($description)
            ->setUser($user)
            ->setBegin($start);

        if ($setEndDate) {
            $end = clone $start;
            $end = $end->modify('+ ' . (rand(self::MIN_MINUTES_PER_ENTRY, self::MAX_MINUTES_PER_ENTRY)) . ' minutes');

            $duration = $end->getTimestamp() - $start->getTimestamp();
            $hourlyRate = (float) $user->getPreferenceValue(UserPreference::HOURLY_RATE);
            $rate = Util::calculateRate($hourlyRate, $duration);

            $entry
                ->setEnd($end)
                ->setRate($rate)
                ->setDuration($duration);
        } else {
            // running entries should be short
            $newBegin = clone $entry->getBegin();
            $newBegin->setTimestamp(time())->modify('- ' . rand(10, self::TIMERANGE_RUNNING) . ' minutes');
            $entry->setBegin($newBegin);
        }

        return $entry;
    }
}
