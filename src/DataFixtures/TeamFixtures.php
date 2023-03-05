<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 *
 * @codeCoverageIgnore
 */
final class TeamFixtures extends Fixture
{
    public const AMOUNT_TEAMS = 10;
    public const MAX_USERS_PER_TEAM = 15;
    public const MAX_PROJECTS_PER_TEAM = 5;

    public static function getGroups(): array
    {
        return ['users', 'team'];
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
        $all = [];

        /** @var Project[] $entries */
        $entries = $manager->getRepository(Project::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    public function load(ObjectManager $manager): void
    {
        $allUsers = $this->getAllUsers($manager);
        $allProjects = $this->getAllProjects($manager);
        $faker = Factory::create();

        for ($i = 1; $i <= self::AMOUNT_TEAMS; $i++) {
            $maxUsers = \count($allUsers) - 1;
            if (self::MAX_USERS_PER_TEAM < $maxUsers) {
                $maxUsers = self::MAX_USERS_PER_TEAM;
            }
            $userCount = mt_rand(1, $maxUsers);

            $maxProjects = \count($allProjects) - 1;
            if (self::MAX_PROJECTS_PER_TEAM < $maxProjects) {
                $maxProjects = self::MAX_PROJECTS_PER_TEAM;
            }
            $projectCount = mt_rand(0, $maxProjects);

            $team = new Team($faker->company() . ' ' . $i);
            $team->addTeamlead($allUsers[array_rand($allUsers)]);

            if ($userCount > 0) {
                $userKeys = array_rand($allUsers, $userCount);
                if (!\is_array($userKeys)) {
                    $userKeys = [$userKeys];
                }
                foreach ($userKeys as $userKey) {
                    $team->addUser($allUsers[$userKey]);
                }
            }

            if ($projectCount > 0) {
                $projectKeys = array_rand($allProjects, $projectCount);
                if (!\is_array($projectKeys)) {
                    $projectKeys = [$projectKeys];
                }
                foreach ($projectKeys as $projectKey) {
                    $team->addProject($allProjects[$projectKey]);
                }
            }

            $manager->persist($team);
        }

        $manager->flush();
        $manager->clear();
    }
}
