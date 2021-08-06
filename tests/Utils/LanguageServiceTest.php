<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\LanguageService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\LanguageService
 */
class LanguageServiceTest extends TestCase
{
    public function testDefaults()
    {
        $sut = new LanguageService('en');
        self::assertFalse($sut->isKnownLanguage('de'));
        self::assertTrue($sut->isKnownLanguage('en'));
        self::assertFalse($sut->isKnownLanguage('xx'));
        self::assertEquals('en', $sut->getDefaultLanguage());
        self::assertEquals(['en'], $sut->getAllLanguages());
    }

    public function testOneLanguage()
    {
        $sut = new LanguageService('de');
        self::assertTrue($sut->isKnownLanguage('de'));
        self::assertFalse($sut->isKnownLanguage('en'));
        self::assertFalse($sut->isKnownLanguage('xx'));
        self::assertEquals('en', $sut->getDefaultLanguage());
        self::assertEquals(['de'], $sut->getAllLanguages());
    }

    public function testMultipleLanguages()
    {
        $sut = new LanguageService('de|it|fr|de_CH|ru|hu|en|zh_CN');
        self::assertTrue($sut->isKnownLanguage('de'));
        self::assertTrue($sut->isKnownLanguage('en'));
        self::assertTrue($sut->isKnownLanguage('en'));
        self::assertFalse($sut->isKnownLanguage('xx'));
        self::assertEquals('en', $sut->getDefaultLanguage());
        // casing is important for locales!
        self::assertEquals(['de', 'it', 'fr', 'de_CH', 'ru', 'hu', 'en', 'zh_CN'], $sut->getAllLanguages());
    }
}
