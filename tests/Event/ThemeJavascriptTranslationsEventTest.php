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
 * @group legacy
 */
class ThemeJavascriptTranslationsEventTest extends TestCase
{
    public const COUNTER = 17;

    public function testDefaultValues(): void
    {
        $sut = new ThemeJavascriptTranslationsEvent(); // @phpstan-ignore new.deprecatedClass

        self::assertCount(self::COUNTER, $sut->getTranslations()); // @phpstan-ignore method.deprecatedClass
    }

    public function testGetterAndSetter(): void
    {
        $sut = new ThemeJavascriptTranslationsEvent(); // @phpstan-ignore new.deprecatedClass
        $sut->setTranslation('foo', 'bar'); // @phpstan-ignore method.deprecatedClass
        $sut->setTranslation('hello', 'world', 'testing'); // @phpstan-ignore method.deprecatedClass

        $result = $sut->getTranslations(); // @phpstan-ignore method.deprecatedClass
        self::assertCount(self::COUNTER + 2, $result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals(['bar', 'messages'], $result['foo']);
        self::assertArrayHasKey('hello', $result);
        self::assertEquals(['world', 'testing'], $result['hello']);
    }
}
