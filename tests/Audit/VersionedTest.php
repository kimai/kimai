<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Audit;

use App\Audit\Versioned;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(Versioned::class)]
class VersionedTest extends TypeTestCase
{
    public function testConstruct(): void
    {
        $sut = new Versioned();
        self::assertInstanceOf(Versioned::class, $sut);
    }

    public function testHasAttributeAttributeOnLoggable(): void
    {
        $reflection = new \ReflectionClass(Versioned::class);
        /** @var array<\ReflectionAttribute<\Attribute>> $attributes */
        $attributes = array_filter($reflection->getAttributes(), fn ($attr) => $attr->getName() === \Attribute::class);
        self::assertCount(1, $attributes, 'Versioned class should have the Attribute attribute');
        $attribute = $attributes[0];
        self::assertEquals(\Attribute::TARGET_PROPERTY, $attribute->getArguments()[0]);
    }
}
