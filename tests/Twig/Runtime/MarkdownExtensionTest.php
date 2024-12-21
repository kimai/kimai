<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Configuration\ConfigLoaderInterface;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\Twig\Runtime\MarkdownExtension;
use App\Utils\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Twig\Runtime\MarkdownExtension
 */
class MarkdownExtensionTest extends TestCase
{
    public function testMarkdownToHtml(): void
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        self::assertEquals('<p><em>test</em></p>', $sut->markdownToHtml('*test*'));
        self::assertEquals('<h1>foobar</h1>', $sut->markdownToHtml('# foobar'));
        self::assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->markdownToHtml('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testTimesheetContent(): void
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, ['timesheet' => ['markdown_content' => false]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        self::assertEquals(
            "- test<br />\n- foo",
            $sut->timesheetContent("- test\n- foo")
        );
        self::assertEquals('', $sut->timesheetContent(null));
        self::assertEquals('', $sut->timesheetContent(''));
        self::assertEquals('# foobar', $sut->timesheetContent('# foobar'));
        self::assertEquals('## foobar', $sut->timesheetContent('## foobar'));
        self::assertEquals('### foobar', $sut->timesheetContent('### foobar'));

        $config = SystemConfigurationFactory::create($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        self::assertEquals(
            "<ul>\n<li>test</li>\n<li>foo</li>\n</ul>\n<p>foo <strong>bar</strong></p>",
            $sut->timesheetContent("- test\n- foo\n\nfoo __bar__")
        );
        self::assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->timesheetContent('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testCommentContent(): void
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, ['timesheet' => ['markdown_content' => false]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        self::assertEquals(
            "<p>- test<br />\n- foo</p>",
            $sut->commentContent("- test\n- foo", true)
        );
        self::assertEquals(
            "- test\n- foo",
            $sut->commentContent("- test\n- foo", false)
        );

        $loremIpsum = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $loremIpsumShort = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l &hellip;';

        self::assertEquals('', $sut->commentContent(null));
        self::assertEquals('', $sut->commentContent(''));
        self::assertEquals('# foobar', $sut->commentContent('# foobar', false));
        self::assertEquals('<p># foobar</p>', $sut->commentContent('# foobar', true));
        self::assertEquals('<p># foobar</p>', $sut->commentContent('# foobar'));
        self::assertEquals('## foobar', $sut->commentContent('## foobar', false));
        self::assertEquals('### foobar', $sut->commentContent('### foobar', false));
        self::assertEquals('<p>' . $loremIpsum . '</p>', $sut->commentContent($loremIpsum, true));
        self::assertEquals('<p>' . $loremIpsum . '</p>', $sut->commentContent($loremIpsum));
        self::assertEquals($loremIpsumShort, $sut->commentContent($loremIpsum, false));

        $config = SystemConfigurationFactory::create($loader, ['timesheet' => ['markdown_content' => true]]);
        $sut = new MarkdownExtension(new Markdown(), $config);
        self::assertEquals(
            "<ul>\n<li>test</li>\n<li>foo</li>\n</ul>\n<p>foo <strong>bar</strong></p>",
            $sut->commentContent("- test\n- foo\n\nfoo __bar__")
        );
        self::assertEquals(
            '<p><a href="javascript%3Aalert(`XSS`)">XSS</a></p>',
            $sut->commentContent('[XSS](javascript:alert(`XSS`))')
        );
    }

    public function testCommentOneLiner(): void
    {
        $loader = $this->createMock(ConfigLoaderInterface::class);
        $config = SystemConfigurationFactory::create($loader, []);
        $sut = new MarkdownExtension(new Markdown(), $config);

        $loremIpsum = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';

        self::assertEquals('', $sut->commentOneLiner(null));
        self::assertEquals('', $sut->commentOneLiner(''));
        self::assertEquals('', $sut->commentOneLiner(null, false));
        self::assertEquals('', $sut->commentOneLiner('', true));

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing &hellip;',
            $sut->commentOneLiner($loremIpsum, false)
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, [$loremIpsum, $loremIpsum, $loremIpsum]), true)
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, [$loremIpsum, $loremIpsum, $loremIpsum]), false)
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt']), true)
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt', 'ssdf']), true)
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt']))
        );

        self::assertEquals(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt &hellip;',
            $sut->commentOneLiner(implode(PHP_EOL, ['Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt', 'ssdf']))
        );
    }
}
