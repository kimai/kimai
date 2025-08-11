<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\Parsedown;
use App\Utils\ParsedownExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parsedown::class)]
#[CoversClass(ParsedownExtension::class)]
class ParsedownExtensionTest extends TestCase
{
    public function testTableContainsCssClasses(): void
    {
        $sut = new ParsedownExtension();
        $html = $sut->parse('
| Item | Price |
|---|---|
| Something | $ 472,78 |
| Another entry | € 111 |
| | |
| Total | A lot |');
        self::assertStringStartsWith('<table class="table">', $html);
    }

    public function testHeaderIsNotConverted(): void
    {
        $sut = new ParsedownExtension();
        $html = $sut->parse('
# Foo
        ');
        self::assertEquals('<p># Foo</p>', $html);
    }
}
