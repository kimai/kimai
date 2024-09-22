<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

/**
 * Defines the sample data to load in during controller tests.
 */
final class TeamFixtures implements TestFixture
{
    use FixturesTrait;

    private int $amount = 0;
    private bool $addCustomer = true;
    /**
     * @var User[]
     */
    private array $skipUser = [];
    private bool $addUser = true;
    /**
     * @var callable
     */
    private $callback;

    /**
     * Will be called prior to persisting the object.
     *
     * @param callable $callback
     */
    public function setCallback(callable $callback): TeamFixtures
    {
        $this->callback = $callback;

        return $this;
    }

    public function setAddCustomer(bool $useCustomer): TeamFixtures
    {
        $this->addCustomer = $useCustomer;

        return $this;
    }

    public function setAddUser(bool $useUser): TeamFixtures
    {
        $this->addUser = $useUser;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): TeamFixtures
    {
        $this->amount = $amount;

        return $this;
    }

    public function addUserToIgnore(User $user): TeamFixtures
    {
        $this->skipUser[] = $user;

        return $this;
    }

    /**
     * @return Team[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        $user = $this->getAllUsers($manager);
        $customer = $this->getAllCustomers($manager);

        for ($i = 0; $i < $this->amount; $i++) {
            $lead = null;
            while (null === $lead) {
                $tmp = $user[array_rand($user)];
                if (!\in_array($tmp, $this->skipUser)) {
                    $lead = $tmp;
                }
            }

            $team = new Team('Testing: ' . uniqid());
            $team->addTeamlead($lead);

            if ($this->addUser) {
                $userToAdd = null;
                while (null === $userToAdd) {
                    $tmp = $user[array_rand($user)];
                    if (!\in_array($tmp, $this->skipUser)) {
                        $userToAdd = $tmp;
                    }
                }
                $team->addUser($userToAdd);
            }

            if ($this->addCustomer) {
                $team->addCustomer($customer[array_rand($customer)]);
            }

            if (null !== $this->callback) {
                \call_user_func($this->callback, $team);
            }
            $manager->persist($team);
            $created[] = $team;
        }

        $manager->flush();

        return $created;
    }
}
