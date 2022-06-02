<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

final class TeamLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Team[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (Team $team) {
            return $team->getId();
        }, $results);

        $loader = new TeamIdLoader($this->entityManager);
        $loader->loadResults($ids);
    }
}
