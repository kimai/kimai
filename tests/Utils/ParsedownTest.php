<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\Parsedown;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parsedown::class)]
class ParsedownTest extends TestCase
{
    public function testTableContainsCssClasses(): void
    {
        $sut = new Parsedown();
        $html = $sut->parse('
| Item | Price |
|---|---|
| Something | $ 472,78 |
| Another entry | € 111 |
| | |
| Total | A lot |');
        self::assertStringStartsWith('<table class="table table-striped table-vcenter">', $html);
    }

    public function testHeaderContainsId(): void
    {
        $sut = new Parsedown();
        $html = $sut->parse('
# Foo
        ');
        self::assertEquals('<h1 id="foo">Foo</h1>', $html);
    }

    public function testHeaderContainsIdDoesNotDuplicate(): void
    {
        $sut = new Parsedown();
        $html = $sut->parse('
# Foo
# Foo
# Foo
        ');
        self::assertEquals('<h1 id="foo">Foo</h1>
<h1 id="foo-1">Foo</h1>
<h1 id="foo-2">Foo</h1>', $html);
    }
}
