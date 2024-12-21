<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Doctrine;

use App\Doctrine\UTCDateTimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\UTCDateTimeType
 */
class UTCDateTimeTypeTest extends TestCase
{
    public function testGetUtc(): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeType::class);
        $type = Type::getType(Types::DATETIME_MUTABLE);

        self::assertInstanceOf(UTCDateTimeType::class, $type);
        $utc = $type::getUtc();
        self::assertSame($utc, $type::getUtc());
        self::assertEquals('UTC', $type::getUtc()->getName());
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToDatabaseValue(AbstractPlatform $platform): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $result = $type->convertToDatabaseValue(null, $platform);
        self::assertNull($result);

        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $date = new \DateTime('2019-01-17 13:30:00');
        $date->setTimezone($berlinTz);

        self::assertEquals('Europe/Berlin', $date->getTimezone()->getName());

        $expected = clone $date;
        $expected->setTimezone($type::getUtc());
        $bla = $expected->format($platform->getDateTimeFormatString());

        /** @var \DateTime $result */
        $result = $type->convertToDatabaseValue($date, $platform);

        self::assertEquals($bla, $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValue(AbstractPlatform $platform): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $result = $type->convertToPHPValue(null, $platform);
        self::assertNull($result);

        $result = $type->convertToPHPValue('2019-01-17 13:30:00', $platform);
        self::assertInstanceOf(\DateTime::class, $result);
        self::assertEquals('UTC', $result->getTimezone()->getName());

        $result = $result->format($platform->getDateTimeFormatString());
        self::assertEquals('2019-01-17 13:30:00', $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValueWithInvalidValue(AbstractPlatform $platform): void
    {
        $this->expectException(ConversionException::class);

        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $type->convertToPHPValue('201xx01-17 13:30:00', $platform);
    }

    /**
     * @return \Doctrine\DBAL\Platforms\MySQLPlatform[][]
     */
    public static function getPlatforms(): array
    {
        return [
            [new MySQLPlatform()],
        ];
    }
}
