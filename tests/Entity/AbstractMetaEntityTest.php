<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\EntityWithMetaFields;
use App\Entity\MetaTableTypeInterface;
use App\Form\Type\DateTimePickerType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractMetaEntityTest extends TestCase
{
    abstract protected function getEntity(): EntityWithMetaFields;

    abstract protected function getMetaEntity(): MetaTableTypeInterface;

    public function testDefaultValues()
    {
        $sut = $this->getMetaEntity();
        self::assertNull($sut->getLabel());
        self::assertNull($sut->getName());
        self::assertNull($sut->getType());
        self::assertNull($sut->getValue());
        self::assertNull($sut->getEntity());
        self::assertIsArray($sut->getConstraints());
        self::assertEmpty($sut->getConstraints());
        self::assertIsArray($sut->getOptions());
        self::assertEmpty($sut->getOptions());
        self::assertFalse($sut->isVisible());
        self::assertFalse($sut->isRequired());
    }

    public function testSetterAndGetter()
    {
        $sut = $this->getMetaEntity();
        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setName('foo-bar'));
        self::assertEquals('foo-bar', $sut->getName());

        self::assertEquals('foo-bar', $sut->getLabel());
        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setLabel('Wohoooo !!!!!'));
        self::assertEquals('Wohoooo !!!!!', $sut->getLabel());
        self::assertEquals('foo-bar', $sut->getName());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setisVisible(true));
        self::assertTrue($sut->isVisible());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setIsRequired(true));
        self::assertTrue($sut->isRequired());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setValue('hello world'));
        self::assertEquals('hello world', $sut->getValue());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setValue(956.32));
        self::assertEquals(956.32, $sut->getValue());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setType(DateTimePickerType::class));
        self::assertEquals(DateTimePickerType::class, $sut->getType());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->addConstraint(new Length(['max' => 10])));
        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->addConstraint(new NotNull([])));
        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->addConstraint(new NotBlank([])));
        self::assertCount(3, $sut->getConstraints());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setConstraints([new Length(['min' => 2])]));
        self::assertCount(1, $sut->getConstraints());

        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setOptions(['foo' => 'bar']));
        self::assertCount(1, $sut->getOptions());

        $entity = $this->getEntity();
        self::assertInstanceOf(MetaTableTypeInterface::class, $sut->setEntity($entity));
        self::assertSame($entity, $sut->getEntity());
    }

    public function testMerge()
    {
        $entity1 = $this->getEntity();
        $entity2 = $this->getEntity();
        $meta1 = $this->getMetaEntity();
        $meta1
            ->setName('foo')
            ->setValue('bar')
            ->setType('blub')
            ->setEntity($entity1)
            ->setConstraints([new NotNull()])
            ->setOptions(['foo' => 'bar', 'bar' => 'foo'])
        ;
        self::assertEquals('foo', $meta1->getName());
        self::assertEquals('bar', $meta1->getValue());
        self::assertEquals('blub', $meta1->getType());
        self::assertEquals('foo', $meta1->getLabel());
        self::assertFalse($meta1->isRequired());
        self::assertFalse($meta1->isVisible());
        self::assertSame($entity1, $meta1->getEntity());
        self::assertCount(1, $meta1->getConstraints());
        self::assertCount(2, $meta1->getOptions());

        $meta2 = $this->getMetaEntity();
        $meta2
            ->setName('foo2')
            ->setLabel('TRALALA')
            ->setValue('bar2')
            ->setType('blub2')
            ->setEntity($entity2)
            ->setIsRequired(true)
            ->setisVisible(true)
            ->setConstraints([new NotBlank(), new Length(['min' => 1])])
            ->setOptions(['foo1' => 'bar1'])
        ;

        self::assertInstanceOf(MetaTableTypeInterface::class, $meta1->merge($meta2));

        self::assertEquals('foo', $meta1->getName());
        self::assertEquals('bar', $meta1->getValue());
        self::assertEquals('blub2', $meta1->getType());
        self::assertEquals('TRALALA', $meta1->getLabel());
        self::assertTrue($meta1->isRequired());
        self::assertTrue($meta1->isVisible());
        self::assertSame($entity1, $meta1->getEntity());
        self::assertCount(2, $meta1->getConstraints());
        self::assertCount(1, $meta1->getOptions());
        self::assertEquals(['foo1' => 'bar1'], $meta1->getOptions());
    }
}
