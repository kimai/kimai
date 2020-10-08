<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Constants;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\EntityWithMetaFields;
use App\Entity\Project;
use App\Entity\Timesheet;

final class Color
{
    public function getTimesheetColor(Timesheet $timesheet): string
    {
        $activity = $timesheet->getActivity();
        if (null !== $activity && $activity->hasColor()) {
            return $activity->getColor();
        }

        $project = $timesheet->getProject();
        if (null !== $project) {
            if ($project->hasColor()) {
                return $project->getColor();
            }
            $customer = $project->getCustomer();
            if ($customer->hasColor()) {
                return $customer->getColor();
            }
        }

        return Constants::DEFAULT_COLOR;
    }

    public function getColor(EntityWithMetaFields $entity, bool $defaultColor = false): ?string
    {
        if ($entity instanceof Timesheet) {
            $color = $this->getTimesheetColor($entity);
            if ($color === Constants::DEFAULT_COLOR && !$defaultColor) {
                $color = null;
            }

            return $color;
        }

        if ($entity instanceof Activity) {
            if ($entity->hasColor()) {
                return $entity->getColor();
            }

            if (null !== $entity->getProject()) {
                $entity = $entity->getProject();
            }
        }

        if ($entity instanceof Project) {
            if ($entity->hasColor()) {
                return $entity->getColor();
            }
            $entity = $entity->getCustomer();
        }

        if ($entity instanceof Customer) {
            if ($entity->hasColor()) {
                return $entity->getColor();
            }
        }

        return $defaultColor ? Constants::DEFAULT_COLOR : null;
    }

    public function getFontContrastColor(string $color): string
    {
        if ($color[0] !== '#') {
            throw new \InvalidArgumentException('Invalid color code given, only #hexadecimal is supported.');
        }

        $color = substr($color, 1);

        if (\strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }
}
