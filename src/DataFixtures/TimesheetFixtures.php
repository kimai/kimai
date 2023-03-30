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
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load add fixture data:
 * bin/console doctrine:fixtures:load
 *
 * Or simply append it to your running installation:
 * bin/console doctrine:fixtures:load --append --group=timesheet
 *
 * @codeCoverageIgnore
 */
final class TimesheetFixtures extends Fixture implements FixtureGroupInterface
{
    public const MIN_TIMESHEETS_PER_USER = 100;
    public const MAX_TIMESHEETS_PER_USER = 1000;
    public const MAX_TIMESHEETS_TOTAL = 10000;
    public const MIN_MINUTES_PER_ENTRY = 15;
    public const MAX_MINUTES_PER_ENTRY = 840; // 14h
    public const MAX_DESCRIPTION_LENGTH = 200;

    public const ADD_TAGS_MAX_ENTRIES = 1000;
    public const MAX_TAG_PER_ENTRY = 3;

    public static function getGroups(): array
    {
        return ['timesheet'];
    }

    public function getRandomFirstDay(): \DateTime
    {
        return new \DateTime(rand(-1095, 14) . ' days');
    }

    public function load(ObjectManager $manager): void
    {
        $allUser = $this->getAllUsers($manager);
        $faker = Factory::create();
        $all = 0;

        foreach ($allUser as $user) {
            $user = $manager->find(User::class, $user->getId());
            // random amount of timesheet entries for every user
            $timesheetForUser = rand(self::MIN_TIMESHEETS_PER_USER, self::MAX_TIMESHEETS_PER_USER);

            $activities = $this->getAllActivities($manager);
            $projects = $this->getAllProjects($manager);

            for ($i = 1; $i <= $timesheetForUser; $i++) {
                if ($all > self::MAX_TIMESHEETS_TOTAL && $i > self::MIN_TIMESHEETS_PER_USER) {
                    break;
                }

                $description = null;
                if ($i % 3 === 0) {
                    $description = $faker->realText($faker->numberBetween(10, self::MAX_DESCRIPTION_LENGTH));
                } elseif ($i % 7 === 0) {
                    $description = substr($faker->text(), 0, self::MAX_DESCRIPTION_LENGTH);
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
            }

            // create active records
            if ($all % 3 === 0) {
                $entry = $this->createTimesheetEntry(
                    $user,
                    $activities[array_rand($activities)],
                    $projects[array_rand($projects)],
                    null,
                    false
                );

                $all++;
                $manager->persist($entry);
            }

            $manager->flush();
            $manager->clear();
        }

        $allTags = $this->getAllTags($manager);
        /** @var array<Timesheet> $entries */
        $entries = $this->findRandom($manager, Timesheet::class, min($all, self::ADD_TAGS_MAX_ENTRIES));
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
        $manager->clear();
    }

    /**
     * @template T of object
     * @param ObjectManager $manager
     * @param class-string<T> $class
     * @param int $amount
     * @return array<int, T>
     */
    private function findRandom(ObjectManager $manager, string $class, int $amount): array
    {
        $qb = $manager->getRepository($class)->createQueryBuilder('entity');
        /** @var array<int> $limits */
        $limits = $qb
            ->select('MIN(entity.id)', 'MAX(entity.id)')
            ->getQuery()
            ->getOneOrNullResult();

        $ids = [];
        for ($i = 0; $i < $amount; $i++) {
            $rand = rand($limits[1], $limits[2]);
            if (!\in_array($rand, $ids)) {
                $ids[] = $rand;
            }
        }

        $qb = $manager->getRepository($class)->createQueryBuilder('entity');

        /** @var array<int, T> $result */
        $result = $qb->where($qb->expr()->in('entity.id', $ids))->setMaxResults($amount)->getQuery()->getResult();

        return $result;
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Tag>
     */
    private function getAllTags(ObjectManager $manager): array
    {
        return $this->findRandom($manager, Tag::class, 50);
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, User>
     */
    private function getAllUsers(ObjectManager $manager): array
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
    private function getAllProjects(ObjectManager $manager): array
    {
        return $this->findRandom($manager, Project::class, 50);
    }

    /**
     * @param ObjectManager $manager
     * @return array<int|string, Activity>
     */
    private function getAllActivities(ObjectManager $manager): array
    {
        return $this->findRandom($manager, Activity::class, 50);
    }

    private function createTimesheetEntry(User $user, Activity $activity, Project $project, ?string $description, bool $setEndDate): Timesheet
    {
        $start = $this->getRandomFirstDay();
        $start = $start->modify('- ' . (rand(1, 86400)) . ' seconds');
        $start->setTimezone(new \DateTimeZone($user->getTimezone()));

        $entry = new Timesheet();
        $entry->setActivity($activity);
        $entry->setProject($activity->getProject() ?? $project);
        $entry->setDescription($description);
        $entry->setUser($user);
        $entry->setBegin($start);

        if ($setEndDate) {
            $end = clone $start;
            $end = $end->modify('+ ' . (rand(self::MIN_MINUTES_PER_ENTRY, self::MAX_MINUTES_PER_ENTRY)) . ' minutes');

            $duration = $end->getTimestamp() - $start->getTimestamp();
            $hourlyRate = (float) $user->getPreferenceValue(UserPreference::HOURLY_RATE);
            $rate = Util::calculateRate($hourlyRate, $duration);

            $entry->setEnd($end);
            $entry->setRate($rate);
            $entry->setDuration($duration);
        } else {
            // running entries should be short
            $newBegin = clone $entry->getBegin();
            $newBegin->setTimestamp(time())->modify('- ' . rand(self::MIN_MINUTES_PER_ENTRY, self::MAX_MINUTES_PER_ENTRY) . ' minutes');
            $entry->setBegin($newBegin);
        }

        return $entry;
    }
}
