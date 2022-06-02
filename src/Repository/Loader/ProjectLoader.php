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
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param Project[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (Project $project) {
            return $project->getId();
        }, $results);

        $loader = new ProjectIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
