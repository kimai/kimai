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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * A trait to be used in all tests that extend the KernelTestCase.
 */
trait KernelTestTrait
{
    /**
     * @param $client HttpKernelBrowser|EntityManager|KernelTestCase
     * @param Fixture $fixture
     */
    protected function importFixture($client, Fixture $fixture)
    {
        if ($client instanceof HttpKernelBrowser) {
            $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        } elseif ($client instanceof EntityManager) {
            $em = $client;
        } elseif ($client instanceof KernelTestCase) {
            $em = $client::$container->get('doctrine.orm.entity_manager');
        } else {
            throw new \InvalidArgumentException('Fixtures need an EntityManager to be imported');
        }

        $loader = new Loader();
        $loader->addFixture($fixture);

        $executor = new ORMExecutor($em, null);
        $executor->execute($loader->getFixtures(), true);
    }

    protected function getUserByName(EntityManager $em, string $username): ?User
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
