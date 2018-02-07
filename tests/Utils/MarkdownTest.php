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
 */
class MarkdownTest extends TestCase
{

    public function testMarkdownToHtml()
    {
        $sut = new Markdown();
        $this->assertEquals('<p><em>test</em></p>', $sut->toHtml('*test*'));
        $this->assertEquals('<h1>foobar</h1>', $sut->toHtml('# foobar'));
    }
}
