<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

trait FixturesTrait
{
    /**
     * @return array<int, User>
     */
    private function getAllUsers(ObjectManager $manager): array
    {
        $all = [];
        /** @var User[] $entries */
        $entries = $manager->getRepository(User::class)->findAll();
        foreach ($entries as $temp) {
            if ($temp->getId() === null) {
                continue;
            }
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @return array<int, Customer>
     */
    private function getAllCustomers(ObjectManager $manager): array
    {
        $all = [];
        /** @var Customer[] $entries */
        $entries = $manager->getRepository(Customer::class)->findAll();
        foreach ($entries as $temp) {
            if ($temp->getId() === null) {
                continue;
            }
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @return array<int, Project>
     */
    private function getAllProjects(ObjectManager $manager): array
    {
        $all = [];
        /** @var Project[] $entries */
        $entries = $manager->getRepository(Project::class)->findAll();
        foreach ($entries as $temp) {
            if ($temp->getId() === null) {
                continue;
            }
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }

    /**
     * @return array<int, Activity>
     */
    private function getAllActivities(ObjectManager $manager): array
    {
        $all = [];
        /** @var Activity[] $entries */
        $entries = $manager->getRepository(Activity::class)->findAll();
        foreach ($entries as $temp) {
            if ($temp->getId() === null) {
                continue;
            }
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }
}
