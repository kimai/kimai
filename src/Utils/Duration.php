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
    public const FORMAT_COLON = 'colon';
    public const FORMAT_NATURAL = 'natural';
    public const FORMAT_SECONDS = 'seconds';

    public const FORMAT_WITH_SECONDS = '%h:%m:%s';
    public const FORMAT_NO_SECONDS = '%h:%m';

    /**
     * Transforms seconds into a duration string.
     *
     * @param int $seconds
     * @param string $format
     * @return string|null
     */
    public function format($seconds, $format = self::FORMAT_NO_SECONDS)
    {
        if (null === $seconds) {
            return null;
        }

        $hour = floor($seconds / 3600);
        $minute = floor(($seconds / 60) % 60);

        $hour = $hour > 9 ? $hour : '0' . $hour;
        $minute = $minute > 9 ? $minute : '0' . $minute;

        $second = $seconds % 60;
        $second = $second > 9 ? $second : '0' . $second;

        $formatted = str_replace('%h', $hour, $format);
        $formatted = str_replace('%m', $minute, $formatted);

        return str_replace('%s', $second, $formatted);
    }

    /**
     * Returns the seconds, which were given as $duration string.
     *
     * @param string $duration
     * @return int
     */
    public function parseDurationString($duration): int
    {
        if (false !== stripos($duration, ':')) {
            return $this->parseDuration($duration, self::FORMAT_COLON);
        }

        if (is_numeric($duration) && $duration == (int) $duration) {
            return $this->parseDuration($duration, self::FORMAT_SECONDS);
        }

        return $this->parseDuration($duration, self::FORMAT_NATURAL);
    }

    /**
     * Returns the seconds, which were given as $mode formatted $duration string.
     *
     * @param string $duration
     * @param string $mode
     * @return int
     * @throws \InvalidArgumentException
     */
    public function parseDuration(string $duration, string $mode): int
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
                if (3 == count($parts)) {
                    $seconds += (int) array_pop($parts);
                }
                $seconds += (int) $parts[1] * 60;
                $seconds += (int) $parts[0] * 3600;
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
                $seconds = (int) $duration;
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
