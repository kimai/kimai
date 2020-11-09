<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\ThemeJavascriptTranslationsEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ThemeJavascriptTranslationsEvent
 */
class ThemeJavascriptTranslationsEventTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new ThemeJavascriptTranslationsEvent();

        $this->assertCount(23, $sut->getTranslations());
    }

    public function testGetterAndSetter()
    {
        $sut = new ThemeJavascriptTranslationsEvent();
        $sut->setTranslation('foo', 'bar');
        $sut->setTranslation('hello', 'world', 'testing');

        $result = $sut->getTranslations();
        self::assertCount(25, $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals(['bar', 'messages'], $result['foo']);
        self::assertArrayHasKey('hello', $result);
        self::assertEquals(['world', 'testing'], $result['hello']);
    }
}
