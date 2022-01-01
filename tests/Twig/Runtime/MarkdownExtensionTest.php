<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Configuration\ConfigLoaderInterface;
use App\Configuration\SystemConfiguration;
use App\Twig\Runtime\MarkdownExtension;
use App\Utils\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\Runtime\MarkdownExtension
 */
class MarkdownExtensionTest extends TestCase
{
    public function testMarkdownToHtml()
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new SystemConfiguration($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        $this->assertEquals('<p><em>test</em></p>', $sut->markdownToHtml('*test*'));
        $this->assertEquals('<p># foobar</p>', $sut->markdownToHtml('# foobar'));
        $this->assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->markdownToHtml('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testTimesheetContent()
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new SystemConfiguration($loader, ['timesheet' => ['markdown_content' => false]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        $this->assertEquals(
            "- test<br />\n- foo",
            $sut->timesheetContent("- test\n- foo")
        );
        $this->assertEquals('', $sut->timesheetContent(null));
        $this->assertEquals('', $sut->timesheetContent(''));

        $config = new SystemConfiguration($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        $this->assertEquals(
            "<ul>\n<li>test</li>\n<li>foo</li>\n</ul>\n<p>foo <strong>bar</strong></p>",
            $sut->timesheetContent("- test\n- foo\n\nfoo __bar__")
        );
        $this->assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->timesheetContent('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testCommentContent()
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new SystemConfiguration($loader, ['timesheet' => ['markdown_content' => false]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        $this->assertEquals(
            "<p>- test<br />\n- foo</p>",
            $sut->commentContent("- test\n- foo", true)
        );
        $this->assertEquals(
            "- test\n- foo",
            $sut->commentContent("- test\n- foo", false)
        );

        $loremIpsum = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';

        $this->assertEquals('', $sut->commentContent(null));
        $this->assertEquals('', $sut->commentContent(''));
        $this->assertEquals('<p>' . $loremIpsum . '</p>', $sut->commentContent($loremIpsum, true));
        $this->assertEquals('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l &hellip;', $sut->commentContent($loremIpsum));

        $config = new SystemConfiguration($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        $this->assertEquals(
            "<ul>\n<li>test</li>\n<li>foo</li>\n</ul>\n<p>foo <strong>bar</strong></p>",
            $sut->commentContent("- test\n- foo\n\nfoo __bar__")
        );
        $this->assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->commentContent('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testCommentOneLiner()
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = new SystemConfiguration($loader, []);
        $sut = new MarkdownExtension(new Markdown(), $config);

        $loremIpsum = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';

        $this->assertEquals('', $sut->commentOneLiner(null));
        $this->assertEquals('', $sut->commentOneLiner(''));
        $this->assertEquals('', $sut->commentOneLiner(null, false));
        $this->assertEquals('', $sut->commentOneLiner('', true));

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing &hellip;',
            $sut->commentOneLiner($loremIpsum, false)
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, [$loremIpsum, $loremIpsum, $loremIpsum]), true)
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, [$loremIpsum, $loremIpsum, $loremIpsum]), false)
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt']), true)
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt', 'ssdf']), true)
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt']))
        );

        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt', 'ssdf']))
        );
    }
}
