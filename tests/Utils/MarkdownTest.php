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
<p>sdfsdf <a href="#test-1">asdfasdf</a> asdfasdf</p>
<h1 id="test-1">test</h1>
<p>aasdfasdf</p>
EOT;

        $markdown = <<<EOT
foo bar

- sdfasdfasdf
- asdfasdfasdf

# test
asdfasdfa

    ssdfsdf
    
sdfsdf [asdfasdf](#test-1) asdfasdf

# test
aasdfasdf
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
