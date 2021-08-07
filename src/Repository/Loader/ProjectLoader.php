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
    private $entityManager;
    private $fullyHydrated;

    public function __construct(EntityManagerInterface $entityManager, bool $fullyHydrated = false)
    {
        $this->entityManager = $entityManager;
        $this->fullyHydrated = $fullyHydrated;
    }

    /**
     * @param Project[] $projects
     */
    public function loadResults(array $projects): void
    {
        $ids = array_map(function (Project $project) {
            return $project->getId();
        }, $projects);

        $loader = new ProjectIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
