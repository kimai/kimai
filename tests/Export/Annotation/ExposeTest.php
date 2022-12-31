<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Annotation;

use App\Export\Annotation\Expose;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Annotation\Expose
 */
class ExposeTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new Expose();

        self::assertNull($sut->name);
        self::assertNull($sut->label);
        self::assertEquals('string', $sut->type);
        self::assertNull($sut->exp);
    }

    public function testWithValues(): void
    {
        $sut = new Expose('name', 'label', 'integer', 'obj.name === null ? 111 : obj.name');

        self::assertEquals('name', $sut->name);
        self::assertEquals('label', $sut->label);
        self::assertEquals('integer', $sut->type);
        self::assertEquals('obj.name === null ? 111 : obj.name', $sut->exp);
    }

    public function testUnknownWithValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type "number" on annotation "App\Export\Annotation\Expose".');

        $sut = new Expose('name', 'label', 'number', 'obj.name === null ? 111 : obj.name');
    }
}
