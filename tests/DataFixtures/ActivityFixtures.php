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
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Defines the sample data to load in during controller tests.
 */
final class ActivityFixtures implements TestFixture
{
    private int $amount = 0;
    private bool $isGlobal = false;
    private ?bool $isVisible = null;
    /**
     * @var callable
     */
    private $callback;
    /**
     * @var array<Project>
     */
    private array $projects = [];

    public function __construct(int $amount = 0)
    {
        $this->amount = $amount;
    }

    /**
     * Will be called prior to persisting the object.
     *
     * @param callable $callback
     * @return ActivityFixtures
     */
    public function setCallback(callable $callback): ActivityFixtures
    {
        $this->callback = $callback;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setIsGlobal(bool $global): ActivityFixtures
    {
        $this->isGlobal = $global;

        return $this;
    }

    public function setIsVisible(bool $visible): ActivityFixtures
    {
        $this->isVisible = $visible;

        return $this;
    }

    public function setAmount(int $amount): ActivityFixtures
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param Project[] $projects
     * @return ActivityFixtures
     */
    public function setProjects(array $projects): ActivityFixtures
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @return Activity[]
     */
    public function load(ObjectManager $manager): array
    {
        $created = [];

        $projects = $this->projects;
        if (empty($projects)) {
            $projects = $this->getAllProjects($manager);
        }
        $faker = Factory::create();

        for ($i = 0; $i < $this->amount; $i++) {
            $project = null;
            if (false === $this->isGlobal) {
                $project = $projects[array_rand($projects)];
            }
            $visible = 0 != $i % 3;
            if (null !== $this->isVisible) {
                $visible = $this->isVisible;
            }
            $activity = new Activity();
            $activity->setProject($project);
            $activity->setName($faker->company() . ($visible ? '' : ' (x)'));
            $activity->setComment($faker->text());
            $activity->setVisible($visible);

            if (null !== $this->callback) {
                \call_user_func($this->callback, $activity);
            }
            $manager->persist($activity);

            $created[] = $activity;
        }

        $manager->flush();

        return $created;
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
}
