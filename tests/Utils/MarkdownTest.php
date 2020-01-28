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
    public function testMarkdownToHtml()
    {
        $sut = new Markdown();
        $this->assertEquals('<p><em>test</em></p>', $sut->toHtml('*test*'));
        $this->assertEquals('<h1 id="foobar">foobar</h1>', $sut->toHtml('# foobar'));
        $html = <<<'EOT'
<p>foo bar</p>
<ul>
<li>sdfasdfasdf</li>
<li>asdfasdfasdf</li>
</ul>
<h1 id="test">test</h1>
<p>asdfasdfa</p>
<pre><code>ssdfsdf</code></pre>
<p><a href="http://example.com/foo-bar.html" target="_blank">http://example.com/foo-bar.html</a><br />
<a href="file:///home/kimai/images/beautiful-flower.png" target="_blank">file:///home/kimai/images/beautiful-flower.png</a></p>
<p>sdfsdf <a href="#test-1">asdfasdf</a> asdfasdf</p>
<h1 id="test-1">test</h1>
<p>aasdfasdf<br />
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
        $this->assertEquals($html, $sut->toHtml($markdown));
    }

    public function testDuplicateIds()
    {
        $sut = new Markdown();

        $html = <<<'EOT'
<h1 id="test">test</h1>
<h2 id="test-1">test</h2>
<h3 id="test-2">test</h3>
<h1 id="test-3">test</h1>
EOT;

        $markdown = <<<EOT
# test
## test
### test
# test
EOT;
        $this->assertEquals($html, $sut->toHtml($markdown));
    }
}
