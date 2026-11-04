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
        $html = $sut->text('
| Item | Price |
|---|---|
| Something | $ 472,78 |
| Another entry | € 111 |
| | |
| Total | A lot |');
        self::assertStringStartsWith('<table class="table table-striped table-vcenter">', $html);
    }

    public function testHeaderIsNotConverted(): void
    {
        $sut = new ParsedownExtension();
        $html = $sut->text('
# Foo
        ');
        self::assertEquals('<p># Foo</p>', $html);
    }

    /**
     * Markdown image syntax must never emit an `<img>` tag, otherwise mPDF
     * (server-side) or the browser (UI) would auto-fetch the remote URL.
     *
     * @see https://github.com/kimai/kimai/security/advisories/GHSA-pj8j-p4g4-4vw8
     */
    public function testMarkdownImageIsRewrittenAsLink(): void
    {
        $sut = new ParsedownExtension();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text('![probe](http://attacker.example/p.png)');

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('href="http://attacker.example/p.png"', $html);
        self::assertStringContainsString('>probe</a>', $html);
    }
}
