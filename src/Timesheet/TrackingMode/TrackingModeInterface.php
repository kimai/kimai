<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use Symfony\Component\HttpFoundation\Request;

/**
 * A tracking-mode defines the behaviour of the user timesheet.
 * It is NOT used for the timesheet administration.
 *
 * @internal do not implement this interface in your bundle, but rather drop a PR to add it to Kimai core
 */
interface TrackingModeInterface
{
    /**
     * Set default values on this new timesheet entity,
     * before form data is rendered/processed.
     *
     * @param Timesheet $timesheet
     * @param Request|null $request
     */
    public function create(Timesheet $timesheet, ?Request $request = null): void;

    /**
     * Whether the user can edit the begin datetime.
     *
     * @return bool
     */
    public function canEditBegin(): bool;

    /**
     * Whether the user can edit the end datetime.
     *
     * @return bool
     */
    public function canEditEnd(): bool;

    /**
     * Whether the user can edit the duration.
     * If this is true, the result of canEditEnd() will be ignored.
     *
     * @return bool
     */
    public function canEditDuration(): bool;

    /**
     * Whether the API can be used to manipulate the start and end times.
     *
     * @return bool
     */
    public function canUpdateTimesWithAPI(): bool;

    /**
     * Whether the real begin and end times are shown in the user timesheet.
     *
     * @return bool
     */
    public function canSeeBeginAndEndTimes(): bool;

    /**
     * Returns a unique identifier for this tracking mode.
     *
     * @return string
     */
    public function getId(): string;
}
