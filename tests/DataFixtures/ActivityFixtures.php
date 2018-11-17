<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
class ActivityFixtures extends Fixture
{
    /**
     * @var int
     */
    protected $amount = 0;
    /**
     * @var bool
     */
    protected $isGlobal = false;
    /**
     * @var bool
     */
    protected $isVisible = null;

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param bool $global
     * @return $this
     */
    public function setIsGlobal(bool $global)
    {
        $this->isGlobal = $global;

        return $this;
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setIsVisible(bool $visible)
    {
        $this->isVisible = $visible;

        return $this;
    }

    /**
     * @param int $amount
     * @return ActivityFixtures
     */
    public function setAmount(int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $projects = $this->getAllProjects($manager);
        $faker = Factory::create();

        // random amount of timesheet entries for every user
        for ($i = 0; $i < $this->amount; $i++) {
            $project = null;
            if (false === $this->isGlobal) {
                $project = $projects[array_rand($projects)];
            }
            $visible = 0 != $i % 3;
            if (null !== $this->isVisible) {
                $visible = $this->isVisible;
            }
            $entity = new Activity();
            $entity
                ->setProject($project)
                ->setName($faker->bs . ($visible ? '' : ' (x)'))
                ->setComment($faker->text)
                ->setVisible($visible)
            ;

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Project[]
     */
    protected function getAllProjects(ObjectManager $manager)
    {
        $all = [];
        /* @var User[] $entries */
        $entries = $manager->getRepository(Project::class)->findAll();
        foreach ($entries as $temp) {
            $all[$temp->getId()] = $temp;
        }

        return $all;
    }
}
