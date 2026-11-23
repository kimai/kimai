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
        $html = $sut->text('
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
        $html = $sut->text('
# Foo
        ');
        self::assertEquals('<h1 id="foo">Foo</h1>', $html);
    }

    public function testHeaderContainsIdDoesNotDuplicate(): void
    {
        $sut = new Parsedown();
        $html = $sut->text('
# Foo
# Foo
# Foo
        ');
        self::assertEquals('<h1 id="foo">Foo</h1>
<h1 id="foo-1">Foo</h1>
<h1 id="foo-2">Foo</h1>', $html);
    }

    /**
     * Markdown image syntax must never emit an `<img>` tag, otherwise mPDF
     * (server-side) or the browser (UI) would auto-fetch the remote URL.
     *
     * @see https://github.com/kimai/kimai/security/advisories/GHSA-pj8j-p4g4-4vw8
     */
    public function testMarkdownImageIsRewrittenAsLink(): void
    {
        $sut = new Parsedown();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text('![probe](http://attacker.example/p.png)');

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('href="http://attacker.example/p.png"', $html);
        self::assertStringContainsString('>probe</a>', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testMarkdownImageWithoutAltUsesUrlAsLabel(): void
    {
        $sut = new Parsedown();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text('![](http://attacker.example/p.png)');

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('>http://attacker.example/p.png</a>', $html);
    }

    public function testReferenceStyleImageIsRewrittenAsLink(): void
    {
        $sut = new Parsedown();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text("![probe][ref]\n\n[ref]: http://attacker.example/p.png");

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('href="http://attacker.example/p.png"', $html);
        self::assertStringContainsString('>probe</a>', $html);
    }

    public function testRawHtmlImageIsEscaped(): void
    {
        $sut = new Parsedown();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text('<img src="http://attacker.example/p.png">');

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img', $html);
    }

    public function testJavascriptUrlInImageIsNeutralised(): void
    {
        $sut = new Parsedown();
        $sut->setSafeMode(true);
        $sut->setMarkupEscaped(true);

        $html = $sut->text('![x](javascript:alert(1))');

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('href="javascript:', $html);
    }
}
