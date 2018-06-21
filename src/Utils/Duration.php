<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * A simple class to help with timesheet record durations.
 */
class Duration
{
    const FORMAT_COLON = 'colon';
    const FORMAT_NATURAL = 'natural';
    const FORMAT_SECONDS = 'seconds';

    /**
     * Transforms seconds into a duration string.
     *
     * @param $seconds
     * @param bool $includeSeconds
     * @return string
     */
    public function format($seconds, $includeSeconds = false)
    {
        $hour = floor($seconds / 3600);
        $minute = floor(($seconds / 60) % 60);

        $hour = $hour > 9 ? $hour : '0' . $hour;
        $minute = $minute > 9 ? $minute : '0' . $minute;

        if (!$includeSeconds) {
            return $hour . ':' . $minute;
        }

        $second = $seconds % 60;
        $second = $second > 9 ? $second : '0' . $second;

        return $hour . ':' . $minute  . ':' . $second;
    }

    /**
     * @param string $duration
     * @return string
     */
    public function parseDurationString($duration)
    {
        if (stripos($duration, ':') !== false) {
            return $this->parseDuration($duration, self::FORMAT_COLON);
        }

        if (is_numeric($duration) && $duration == (int)$duration) {
            return $this->parseDuration($duration, self::FORMAT_SECONDS);
        }

        return $this->parseDuration($duration, self::FORMAT_NATURAL);
    }

    /**
     * @param string $duration
     * @param string $mode
     * @return int
     * @throws \InvalidArgumentException
     */
    public function parseDuration(string $duration, string $mode)
    {
        if (empty($duration)) {
            return 0;
        }

        $seconds = 0;

        switch ($mode) {
            case self::FORMAT_COLON:
                $parts = explode(':', $duration);
                if (count($parts) < 2) {
                    throw new \InvalidArgumentException('Colon format cannot parse: ' . $duration);
                }
                $seconds = 0;
                if (count($parts) == 3) {
                    $seconds += array_pop($parts);
                }
                $seconds += $parts[1] * 60;
                $seconds += $parts[0] * 3600;
                break;

            case self::FORMAT_NATURAL:
                try {
                    $interval = new \DateInterval('PT' . strtoupper($duration));
                    $reference = new \DateTimeImmutable();
                    $endTime = $reference->add($interval);
                    $seconds = $endTime->getTimestamp() - $reference->getTimestamp();
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException('Invalid input for natural format: ' . $duration);
                }
                break;

            case self::FORMAT_SECONDS:
                $seconds = $duration;
                break;

            default:
                throw new \InvalidArgumentException('Invalid duration format: ' . $mode);
        }

        if ($seconds < 0) {
            return 0;
        }

        return $seconds;
    }
}
