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
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\UTCDateTimeType
 */
class UTCDateTimeTypeTest extends TestCase
{
    public function testGetUtc()
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $this->assertInstanceOf(UTCDateTimeType::class, $type);
        $utc = $type::getUtc();
        $this->assertSame($utc, $type::getUtc());
        $this->assertEquals('UTC', $type::getUtc()->getName());
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToDatabaseValue(AbstractPlatform $platform)
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $result = $type->convertToDatabaseValue(null, $platform);
        $this->assertNull($result);

        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $date = new \DateTime('2019-01-17 13:30:00');
        $date->setTimezone($berlinTz);

        $this->assertEquals('Europe/Berlin', $date->getTimezone()->getName());

        $expected = clone $date;
        $expected->setTimezone($type::getUtc());
        $bla = $expected->format($platform->getDateTimeFormatString());

        /** @var \DateTime $result */
        $result = $type->convertToDatabaseValue($date, $platform);

        $this->assertEquals($bla, $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValue(AbstractPlatform $platform)
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $result = $type->convertToPHPValue(null, $platform);
        $this->assertNull($result);

        $result = $type->convertToPHPValue('2019-01-17 13:30:00', $platform);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('UTC', $result->getTimezone()->getName());

        $result = $result->format($platform->getDateTimeFormatString());
        $this->assertEquals('2019-01-17 13:30:00', $result);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testConvertToPHPValueWithInvalidValue(AbstractPlatform $platform)
    {
        $this->expectException(ConversionException::class);

        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $type->convertToPHPValue('201xx01-17 13:30:00', $platform);
    }

    /**
     * @dataProvider getPlatforms
     */
    public function testRequiresSQLCommentHint(AbstractPlatform $platform)
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);
        self::assertTrue($type->requiresSQLCommentHint($platform));
    }

    public function getPlatforms()
    {
        return [
            [new MySqlPlatform()],
            [new SqlitePlatform()],
        ];
    }
}
