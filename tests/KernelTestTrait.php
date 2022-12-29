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
use App\Tests\DataFixtures\TestFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A trait to be used in all tests that extend the KernelTestCase.
 */
trait KernelTestTrait
{
    public function getEntityManager(): EntityManagerInterface
    {
        if (!$this instanceof KernelTestCase) {
            throw new \Exception('KernelTestTrait can only be used in a KernelTestCase');
        }

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        return $em;
    }

    protected function importFixture(TestFixture $fixture): array
    {
        return $fixture->load($this->getEntityManager());
    }

    protected function getUserByName(string $username): User
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user === null) {
            throw new \InvalidArgumentException('Unknown user: ' . $username);
        }

        return $user;
    }

    protected function getUserByRole(string $role = User::ROLE_USER): User
    {
        $name = match ($role) {
            User::ROLE_SUPER_ADMIN => UserFixtures::USERNAME_SUPER_ADMIN,
            User::ROLE_ADMIN => UserFixtures::USERNAME_ADMIN,
            User::ROLE_TEAMLEAD => UserFixtures::USERNAME_TEAMLEAD,
            User::ROLE_USER => UserFixtures::USERNAME_USER,
            default => throw new \InvalidArgumentException('Unknown user by role: ' . $role),
        };

        return $this->getUserByName($name);
    }
}
