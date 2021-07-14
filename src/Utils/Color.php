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
    // @see https://clrs.cc
    public const PALETTE_1 = ['#0074D9', '#7FDBFF', '#39CCCC', '#B10DC9', '#F012BE', '#85144b', '#FF4136', '#FF851B', '#FFDC00', '#3D9970', '#2ECC40', '#01FF70', '#AAAAAA', '#DDDDDD'];
    // old avatar color set
    public const PALETTE_2 = ['#a972c9', '#9C27B0', '#673AB7', '#5319e7', '#041fd1', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4', '#006b75', '#009688', '#00bb32', '#4CAF50', '#8BC34A', '#CDDC39', '#FFC107', '#FF9800', '#FF5722', '#f41a00', '#E91E63', '#b60205', '#cc317c', '#d82d80', '#e135f4', '#2d3748', '#4a5568', '#718096'];
    // all mixed together
    public const PALETTE_3 = ['#AAAAAA', '#DDDDDD', '#a972c9', '#9C27B0', '#673AB7', '#041fd1', '#5319e7', '#3F51B5', '#0074D9', '#2196F3', '#03A9F4', '#7FDBFF', '#39CCCC', '#00BCD4', '#006b75', '#009688', '#00bb32', '#4CAF50', '#3D9970', '#2ECC40', '#01FF70', '#8BC34A', '#CDDC39', '#FFDC00', '#FFC107', '#FF851B', '#FF9800', '#FF5722', '#f41a00', '#E91E63', '#85144b', '#b60205', '#FF4136', '#cc317c', '#F012BE', '#d82d80', '#B10DC9', '#e135f4', '#2d3748', '#4a5568', '#718096'];

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

    public function getRandom(?string $input = null): string
    {
        if ($input === null) {
            return $this->getRandomColor();
        }

        return $this->getRandomFromPalette($input);
    }

    public function getRandomColor(): string
    {
        return sprintf('#%06x', rand(0, 16777215));
    }

    public function getRandomFromPalette(string $input): string
    {
        $id = 0;
        for ($pos = 0; $pos < \strlen($input); $pos++) {
            $id += mb_ord($input[$pos], 'UTF-8');
        }

        $colors = self::PALETTE_3;
        $key = $id % \count($colors);

        return $colors[$key];
    }

    public function getFontContrastColor(string $color): string
    {
        if (empty($color) || $color[0] !== '#') {
            // do not throw exception on invalid colors, as they were not validated in the past
            $color = Constants::DEFAULT_COLOR;
        }

        $color = substr($color, 1);
        $length = \strlen($color);

        if ($length === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        } elseif ($length !== 6) {
            $color = substr(Constants::DEFAULT_COLOR, 1);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }
}
