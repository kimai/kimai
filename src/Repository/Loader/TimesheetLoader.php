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
    private $entityManager;
    private $fullyHydrated;

    public function __construct(EntityManagerInterface $entityManager, bool $fullyHydrated = false)
    {
        $this->entityManager = $entityManager;
        $this->fullyHydrated = $fullyHydrated;
    }

    /**
     * @param Timesheet[] $timesheets
     */
    public function loadResults(array $timesheets): void
    {
        $ids = array_map(function (Timesheet $timesheet) {
            return $timesheet->getId();
        }, $timesheets);

        $loader = new TimesheetIdLoader($this->entityManager, $this->fullyHydrated);
        $loader->loadResults($ids);
    }
}
