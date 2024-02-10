<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\UserPreference;
use App\Form\Type\YesNoType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * @covers \App\Entity\UserPreference
 */
class UserPreferenceTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new UserPreference('foo');
        self::assertTrue($sut->isEnabled());
        self::assertEquals('default', $sut->getSection());
        self::assertEquals(1000, $sut->getOrder());
        self::assertNull($sut->getValue());
        self::assertIsArray($sut->getConstraints());
        self::assertEmpty($sut->getConstraints());
        self::assertNull($sut->getId());
        self::assertEquals('foo', $sut->getLabel());
        self::assertEquals('foo', $sut->getName());
        self::assertIsArray($sut->getOptions());
        self::assertEmpty($sut->getOptions());
        self::assertNull($sut->getType());
        self::assertNull($sut->getUser());
    }

    public function testGetValueChangesReturnTypeOnOtherType(): void
    {
        $sut = new UserPreference('foo');
        $sut->setValue('1');
        self::assertSame('1', $sut->getValue());
        $sut->setType(IntegerType::class);
        self::assertSame(1, $sut->getValue());
        $sut->setType(YesNoType::class);
        self::assertTrue($sut->getValue());
        $sut->setValue('0');
        $sut->setType(CheckboxType::class);
        self::assertFalse($sut->getValue());
    }

    public function testGetLabelWithLabelOption(): void
    {
        $sut = new UserPreference('foo');
        self::assertEquals('foo', $sut->getLabel());
        $sut->setOptions(['label' => 'bar']);
        self::assertEquals('bar', $sut->getLabel());
    }
}
