<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * A tracking-mode defines the behaviour of the user timesheet.
 * It is NOT used for the timesheet administration.
 *
 * @internal do not implement this interface in your bundle, but rather drop a PR to add it to Kimai core
 */
#[AutoconfigureTag]
interface TrackingModeInterface
{
    /**
     * Set default values on this new timesheet entity, before form data is rendered/processed.
     */
    public function create(Timesheet $timesheet, ?Request $request = null): void;

    /**
     * Whether the user can edit the begin datetime.
     */
    public function canEditBegin(): bool;

    /**
     * Whether the user can edit the end datetime.
     */
    public function canEditEnd(): bool;

    /**
     * Whether the user can edit the duration.
     *
     * If this is true, the result of canEditEnd() will be ignored.
     */
    public function canEditDuration(): bool;

    /**
     * Whether the API can be used to manipulate the start and end times.
     */
    public function canUpdateTimesWithAPI(): bool;

    /**
     * Returns the edit template path for this tracking mode for regular user mode.
     */
    public function getEditTemplate(): string;

    /**
     * Whether the real begin and end times are shown in the user timesheet.
     */
    public function canSeeBeginAndEndTimes(): bool;

    /**
     * Returns a unique identifier for this tracking mode.
     */
    public function getId(): string;
}
