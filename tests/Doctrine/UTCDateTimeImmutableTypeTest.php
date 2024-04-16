<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Doctrine;

use App\Doctrine\UTCDateTimeImmutableType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\UTCDateTimeImmutableType
 */
class UTCDateTimeImmutableTypeTest extends TestCase
{
    public function testGetUtc(): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeImmutableType::class);
        /** @var UTCDateTimeImmutableType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $this->assertInstanceOf(UTCDateTimeImmutableType::class, $type);
        $utc = $type::getUtc();
        $this->assertSame($utc, $type::getUtc());
        $this->assertEquals('UTC', $type::getUtc()->getName());
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToDatabaseValue(AbstractPlatform $platform): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeImmutableType::class);
        /** @var UTCDateTimeImmutableType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $result = $type->convertToDatabaseValue(null, $platform);
        $this->assertNull($result);

        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $date = new \DateTimeImmutable('2019-01-17 13:30:00');
        $date = $date->setTimezone($berlinTz);

        $this->assertEquals('Europe/Berlin', $date->getTimezone()->getName());

        $expected = clone $date;
        $expected = $expected->setTimezone($type::getUtc());
        $bla = $expected->format($platform->getDateTimeFormatString());

        /** @var \DateTime $result */
        $result = $type->convertToDatabaseValue($date, $platform);

        $this->assertEquals($bla, $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValue(AbstractPlatform $platform): void
    {
        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeImmutableType::class);
        /** @var UTCDateTimeImmutableType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $result = $type->convertToPHPValue(null, $platform);
        $this->assertNull($result);

        $result = $type->convertToPHPValue('2019-01-17 13:30:00', $platform);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertEquals('UTC', $result->getTimezone()->getName());

        $result = $result->format($platform->getDateTimeFormatString());
        $this->assertEquals('2019-01-17 13:30:00', $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValueWithInvalidValue(AbstractPlatform $platform): void
    {
        $this->expectException(ConversionException::class);

        Type::overrideType(Types::DATETIME_MUTABLE, UTCDateTimeImmutableType::class);
        /** @var UTCDateTimeImmutableType $type */
        $type = Type::getType(Types::DATETIME_MUTABLE);

        $type->convertToPHPValue('201xx01-17 13:30:00', $platform);
    }

    /**
     * @return \Doctrine\DBAL\Platforms\MySQLPlatform[][]
     */
    public function getPlatforms(): array
    {
        return [
            [new MySQLPlatform()],
        ];
    }
}
