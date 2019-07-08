<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectLoader implements LoaderInterface
{
    /**
     * @var ProjectIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new ProjectIdLoader($entityManager);
    }

    /**
     * @param Project[] $projects
     */
    public function loadResults(array $projects): void
    {
        $ids = array_map(function (Project $project) {
            return $project->getId();
        }, $projects);

        $this->loader->loadResults($ids);
    }
}
