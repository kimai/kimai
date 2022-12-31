<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\Timesheet;

/**
 * A calculator is called before a Timesheet entity will be updated.
 * These classes will normally be used when calculating duration or rates.
 */
interface CalculatorInterface
{
    /**
     * All necessary changes need to be applied on the given $record.
     *
     * @param Timesheet $record
     * @param array<string, array<mixed, mixed>> $changeset
     * @return void
     */
    public function calculate(Timesheet $record, array $changeset): void;

    /*
     * Default priority is 1000 (after all system Calculator were executed).
     * The higher the priority the later it will be executed.
     *
     * @return int
     */
    public function getPriority(): int;
}
