<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManager;

/**
 * A trait to be used in all tests that extend the KernelTestCase.
 */
trait KernelTestTrait
{
    /**
     * @param EntityManager $em
     * @param Fixture $fixture
     */
    protected function importFixture(EntityManager $em, Fixture $fixture)
    {
        $loader = new Loader();
        $loader->addFixture($fixture);

        $executor = new ORMExecutor($em, null);
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @param EntityManager $em
     * @param string $username
     * @return User|null
     */
    protected function getUserByName(EntityManager $em, string $username)
    {
        return $em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /**
     * @param EntityManager $em
     * @param string $role
     * @return User|null
     */
    protected function getUserByRole(EntityManager $em, string $role = User::ROLE_USER)
    {
        $name = null;

        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $name = UserFixtures::USERNAME_SUPER_ADMIN;
                break;

            case User::ROLE_ADMIN:
                $name = UserFixtures::USERNAME_ADMIN;
                break;

            case User::ROLE_TEAMLEAD:
                $name = UserFixtures::USERNAME_TEAMLEAD;
                break;

            case User::ROLE_USER:
                $name = UserFixtures::USERNAME_USER;
                break;

            default:
                return null;
        }

        return $this->getUserByName($em, $name);
    }
}
