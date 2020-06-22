<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;

final class ActivityLoader implements LoaderInterface
{
    /**
     * @var ActivityIdLoader
     */
    private $loader;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new ActivityIdLoader($entityManager);
    }

    /**
     * @param Activity[] $activities
     */
    public function loadResults(array $activities): void
    {
        $ids = array_map(function (Activity $activity) {
            return $activity->getId();
        }, $activities);

        $this->loader->loadResults($ids);
    }
}
