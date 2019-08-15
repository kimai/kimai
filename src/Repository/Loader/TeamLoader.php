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
    /**
     * @var TeamIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new TeamIdLoader($entityManager);
    }

    /**
     * @param Team[] $teams
     */
    public function loadResults(array $teams): void
    {
        $ids = array_map(function (Team $team) {
            return $team->getId();
        }, $teams);

        $this->loader->loadResults($ids);
    }
}
