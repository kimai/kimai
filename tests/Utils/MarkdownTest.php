<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\Markdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\Markdown
 * @covers \App\Utils\ParsedownExtension
 */
class MarkdownTest extends TestCase
{
    public function testMarkdownToHtml(): void
    {
        $sut = new Markdown();
        self::assertEquals('<p><em>test</em></p>', $sut->toHtml('*test*'));
        self::assertEquals('<p># foobar</p>', $sut->toHtml('# foobar'));
        $html = <<<'EOT'
            <p>foo bar</p>
            <ul>
            <li>sdfasdfasdf</li>
            <li>asdfasdfasdf</li>
            </ul>
            <p># test<br />
            asdfasdfa</p>
            <pre><code>ssdfsdf</code></pre>
            <p><a href="http://example.com/foo-bar.html" target="_blank">http://example.com/foo-bar.html</a><br />
            <a href="file:///home/kimai/images/beautiful-flower.png" target="_blank">file:///home/kimai/images/beautiful-flower.png</a></p>
            <p>sdfsdf <a href="#test-1">asdfasdf</a> asdfasdf</p>
            <p># test<br />
            aasdfasdf<br />
            1111<br />
            222</p>
            EOT;

        $markdown = <<<EOT
            foo bar

            - sdfasdfasdf
            - asdfasdfasdf

            # test
            asdfasdfa

                ssdfsdf
                
            http://example.com/foo-bar.html
            file:///home/kimai/images/beautiful-flower.png

            sdfsdf [asdfasdf](#test-1) asdfasdf

            # test
            aasdfasdf
            1111
            222
            EOT;
        self::assertEquals($html, $sut->toHtml($markdown));
    }

    public function testDuplicateIds(): void
    {
        $sut = new Markdown();

        $html = <<<'EOT'
            <p># test<br />
            ## test<br />
            ### test<br />
            # test</p>
            EOT;

        $markdown = <<<EOT
            # test
            ## test
            ### test
            # test
            EOT;
        self::assertEquals($html, $sut->toHtml($markdown));
    }

    public function testLinksAreSanitized(): void
    {
        $sut = new Markdown();

        $html = <<<'EOT'
            <p><a href="javascript%3Aalert(`XSS`)">XSS</a><br />
            <a href="javascript%3Aalert(&quot;XSS&quot;)">XSS</a><br />
            <a href="javascript%3Aalert(&#039;XSS&#039;)">XSS</a></p>
            EOT;

        $markdown = <<<EOT
            [XSS](javascript:alert(`XSS`))
            [XSS](javascript:alert("XSS"))
            [XSS](javascript:alert('XSS'))
            EOT;
        self::assertEquals($html, $sut->toHtml($markdown));
    }
}
