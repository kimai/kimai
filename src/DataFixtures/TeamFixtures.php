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
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
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
class TeamFixtures extends Fixture implements DependentFixtureInterface
{
    public const AMOUNT_TEAMS = 10;
    public const MAX_USERS_PER_TEAM = 15;
    public const MAX_PROJECTS_PER_TEAM = 5;

    // lower batch size, as user preferences are added in the same run
    public const BATCH_SIZE = 50;

    /**
     * @return class-string[]
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $allUsers = $this->getAllUsers($manager);
        $allProjects = $this->getAllProjects($manager);
        $faker = Factory::create();

        for ($i = 1; $i <= self::AMOUNT_TEAMS; $i++) {
            $maxUsers = \count($allUsers) - 1;
            if (self::MAX_USERS_PER_TEAM < $maxUsers) {
                $maxUsers = self::MAX_USERS_PER_TEAM;
            }
            $userCount = mt_rand(0, $maxUsers);

            $maxProjects = \count($allProjects) - 1;
            if (self::MAX_PROJECTS_PER_TEAM < $maxProjects) {
                $maxProjects = self::MAX_PROJECTS_PER_TEAM;
            }
            $projectCount = mt_rand(0, $maxProjects);

            $team = new Team();
            $team
                ->setName($faker->company . ' ' . $i)
                ->setTeamLead($allUsers[array_rand($allUsers)])
            ;

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

            if ($i % self::BATCH_SIZE === 0) {
                $manager->flush();
                $manager->clear(Team::class);
            }
        }

        $manager->flush();
        $manager->clear(Team::class);
    }
}
