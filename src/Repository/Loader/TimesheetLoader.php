<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Timesheet;
use Doctrine\ORM\EntityManagerInterface;

final class TimesheetLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param Timesheet[] $results
     */
    public function loadResults(array $results): void
    {
        $ids = array_map(function (Timesheet $timesheet) {
            return $timesheet->getId();
        }, $results);

        $loader = new TimesheetIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
