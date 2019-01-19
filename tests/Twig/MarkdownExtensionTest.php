<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\MarkdownExtension;
use App\Utils\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\MarkdownExtension
 */
class MarkdownExtensionTest extends TestCase
{
    public function testGetFilters()
    {
        $sut = new MarkdownExtension(new Markdown());
        $filters = $sut->getFilters();
        $this->assertCount(2, $filters);
        $this->assertEquals('md2html', $filters[0]->getName());
        $this->assertEquals('desc2html', $filters[1]->getName());
    }

    public function testMarkdownToHtml()
    {
        $sut = new MarkdownExtension(new Markdown());
        $this->assertEquals('<p><em>test</em></p>', $sut->markdownToHtml('*test*'));
        $this->assertEquals('<h1 id="foobar">foobar</h1>', $sut->markdownToHtml('# foobar'));
    }

    public function testTimesheetContent()
    {
        $sut = new MarkdownExtension(new Markdown(), false);
        $this->assertEquals(
            "- test<br />\n- foo",
            $sut->timesheetContent("- test\n- foo")
        );
        $this->assertEquals('', $sut->timesheetContent(null));
        $this->assertEquals('', $sut->timesheetContent(''));

        $sut = new MarkdownExtension(new Markdown(), true);
        $this->assertEquals(
            "<ul>\n<li>test</li>\n<li>foo</li>\n</ul>\n<p>foo <strong>bar</strong></p>",
            $sut->timesheetContent("- test\n- foo\n\nfoo __bar__")
        );
    }
}
