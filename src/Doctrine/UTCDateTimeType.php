<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Types;

final class UTCDateTimeType extends DateTimeType
{
    /**
     * @var \DateTimeZone|null
     */
    private static ?\DateTimeZone $utc = null;

    /**
     * @param T $value
     * @param AbstractPlatform $platform
     * @return (T is null ? null : string)
     * @template T<\DateTime>
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTime) {
            $value = clone $value;
            $value->setTimezone(self::getUtc());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public static function getUtc(): \DateTimeZone
    {
        if (self::$utc === null) {
            self::$utc = new \DateTimeZone('UTC');
        }

        return self::$utc;
    }

    /**
     * @param mixed $value
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?\DateTime
    {
        if (null === $value || $value instanceof \DateTime) {
            return $value;
        }

        if (\is_string($value)) {
            $converted = \DateTime::createFromFormat(
                $platform->getDateTimeFormatString(),
                $value,
                self::getUtc()
            );

            if ($converted !== false) {
                return $converted;
            }
        }

        throw ConversionException::conversionFailedFormat(
            $value,
            Types::DATETIME_MUTABLE,
            $platform->getDateTimeFormatString()
        );
    }
}
