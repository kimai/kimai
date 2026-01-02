<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Audit;

use App\Audit\Loggable;
use App\Entity\CustomerMeta;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(Loggable::class)]
class LoggableTest extends TypeTestCase
{
    public function testConstruct(): void
    {
        $sut = new Loggable(CustomerMeta::class);
        self::assertEquals(CustomerMeta::class, $sut->customFieldClass);
    }

    public function testHasAttributeAttributeOnLoggable(): void
    {
        $reflection = new \ReflectionClass(Loggable::class);
        /** @var array<\ReflectionAttribute<\Attribute>> $attributes */
        $attributes = array_filter($reflection->getAttributes(), fn ($attr) => $attr->getName() === \Attribute::class);
        self::assertCount(1, $attributes, 'Loggable class should have the Attribute attribute');
        $attribute = $attributes[0];
        self::assertEquals(\Attribute::TARGET_CLASS, $attribute->getArguments()[0]);
    }
}
