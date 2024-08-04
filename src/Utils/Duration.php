<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

/**
 * Convert duration strings into seconds.
 */
final class Duration
{
    public const FORMAT_COLON = 'colon';
    public const FORMAT_NATURAL = 'natural';
    public const FORMAT_DECIMAL = 'decimal';
    public const FORMAT_DEFAULT = '%h:%m';

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|null $seconds
     * @param string $format
     * @return string|null
     */
    public function format(?int $seconds, string $format = self::FORMAT_DEFAULT): ?string
    {
        if (null === $seconds) {
            return null;
        }

        if ($seconds < 0) {
            if ($seconds <= -60) {
                $format = '-' . $format;
            }
            $seconds = abs($seconds);
        }

        $hour = (int) floor($seconds / 3600);
        $minute = (int) floor((int) ($seconds / 60) % 60);
        $minute = $minute > 9 ? $minute : '0' . $minute;

        $formatted = str_replace('%h', (string) $hour, $format);

        return str_replace('%m', (string) $minute, $formatted);
    }

    /**
     * Returns the seconds, which were given as $duration string.
     *
     * @param string $duration
     * @return int
     */
    public function parseDurationString(string $duration): int
    {
        if (false !== stripos($duration, ':')) {
            return $this->parseDuration($duration, self::FORMAT_COLON);
        }

        if (str_contains($duration, '.') || str_contains($duration, ',') || is_numeric($duration)) {
            return $this->parseDuration($duration, self::FORMAT_DECIMAL);
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

        return match ($mode) {
            self::FORMAT_COLON => $this->parseColonFormat($duration),
            self::FORMAT_NATURAL => $this->parseNaturalFormat($duration),
            self::FORMAT_DECIMAL => $this->parseDecimalFormat($duration),
            default => throw new \InvalidArgumentException(\sprintf('Unsupported duration format "%s"', $mode)),
        };
    }

    private function parseNaturalFormat(string $duration): int
    {
        try {
            $interval = new \DateInterval('PT' . strtoupper($duration));
            $reference = new \DateTimeImmutable();
            $endTime = $reference->add($interval);

            return $endTime->getTimestamp() - $reference->getTimestamp();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid input for natural format: ' . $duration);
        }
    }

    private function parseDecimalFormat(string $duration): int
    {
        $duration = str_replace(',', '.', $duration);
        $duration = (float) $duration;
        $duration = $duration * 3600;

        return (int) $duration;
    }

    private function parseColonFormat(string $duration): int
    {
        $parts = explode(':', $duration);
        if (\count($parts) < 2 || \count($parts) > 3) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid colon format given in "%s"', $duration)
            );
        }

        $i = 0;
        foreach ($parts as $part) {
            if (\strlen($part) === 0) {
                throw new \InvalidArgumentException(
                    \sprintf('Colon format cannot parse "%s"', $duration)
                );
            }
            // the entire time could be negative
            if ($i++ > 0 && ((int) $part) < 0) {
                throw new \InvalidArgumentException(
                    \sprintf('Negative input is not allowed in "%s"', $duration)
                );
            }
        }

        $seconds = 0;

        if (3 === \count($parts)) {
            $seconds += (int) array_pop($parts);
        }

        $seconds += (int) $parts[1] * 60;
        $seconds += abs((int) $parts[0] * 3600);

        if ($duration[0] === '-') {
            $seconds = $seconds * -1;
        }

        return $seconds;
    }
}
