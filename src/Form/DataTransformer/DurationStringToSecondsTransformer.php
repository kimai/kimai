<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\DataTransformer;

use App\Utils\Duration;
use App\Validator\Constraints\Duration as DurationConstraint;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<string|int|null, string|null>
 */
final class DurationStringToSecondsTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            if (!\is_int($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            if (!\is_int($value)) {
                // do not throw an exception, that would break the frontend, make it null / empty instead
                return null;
            }

            return (new Duration())->format($value);
        } catch (\Exception | \TypeError $e) {
            throw new TransformationFailedException($e->getMessage());
        }
    }

    public function reverseTransform(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return 0;
        }

        if (\is_int($value) || \is_float($value)) {
            $value = (string) $value;
        }

        // we need this one here, because the data transformer is executed BEFORE the constraint is called
        if (!preg_match((new DurationConstraint())->pattern, $value)) {
            throw new TransformationFailedException('Invalid duration format given');
        }

        try {
            $seconds = (new Duration())->parseDurationString($value);

            // DateTime throws if a duration with too many seconds is passed and an amount of so
            // many seconds is likely not required in a time-tracking application ;-)
            if ($seconds > 315360000000000) {
                throw new TransformationFailedException('Maximum duration exceeded.');
            }

            return $seconds;
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage());
        }
    }
}
