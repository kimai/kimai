<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

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

    // lower batch size, as user preferences are added in the same run
    public const BATCH_SIZE = 50;

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $allUsers = $this->getAllUsers($manager);
        $faker = Factory::create();

        for ($i = 1; $i <= self::AMOUNT_TEAMS; $i++) {
            $userCount = rand(1, (count($allUsers) - 1));

            $team = new Team();
            $team
                ->setName($faker->company . ' ' . $i)
                ->setTeamLead($allUsers[array_rand($allUsers)])
            ;

            $userKeys = array_rand($allUsers, $userCount);
            foreach ($userKeys as $userKey) {
                $team->addUser($allUsers[$userKey]);
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
