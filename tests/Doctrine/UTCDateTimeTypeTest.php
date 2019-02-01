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
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Doctrine\UTCDateTimeType
 */
class UTCDateTimeTypeTest extends KernelTestCase
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $registry = $kernel->getContainer()->get('doctrine');
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $registry->getConnection();
        $this->platform = $connection->getDatabasePlatform();
    }

    public function testGetUtc()
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $this->assertInstanceOf(UTCDateTimeType::class, $type);
        $utc = $type->getUtc();
        $this->assertSame($utc, $type->getUtc());
        $this->assertEquals('UTC', $type->getUtc()->getName());
    }

    public function testConvertToDatabaseValue()
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $result = $type->convertToDatabaseValue(null, $this->platform);
        $this->assertNull($result);

        $berlinTz = new \DateTimeZone('Europe/Berlin');
        $date = new \DateTime('2019-01-17 13:30:00');
        $date->setTimezone($berlinTz);

        $this->assertEquals('Europe/Berlin', $date->getTimezone()->getName());

        $expected = clone $date;
        $expected->setTimezone($type->getUtc());
        $bla = $expected->format($this->platform->getDateTimeFormatString());

        /** @var \DateTime $result */
        $result = $type->convertToDatabaseValue($date, $this->platform);

        $this->assertEquals($bla, $result);
    }

    public function testConvertToPHPValue()
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $result = $type->convertToPHPValue(null, $this->platform);
        $this->assertNull($result);

        $result = $type->convertToPHPValue('2019-01-17 13:30:00', $this->platform);
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('UTC', $result->getTimezone()->getName());

        $result = $result->format($this->platform->getDateTimeFormatString());
        $this->assertEquals('2019-01-17 13:30:00', $result);
    }

    /**
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testConvertToPHPValueWithInvalidValue()
    {
        Type::overrideType(Type::DATETIME, UTCDateTimeType::class);
        /** @var UTCDateTimeType $type */
        $type = Type::getType(Type::DATETIME);

        $result = $type->convertToPHPValue('201xx01-17 13:30:00', $this->platform);
    }
}
