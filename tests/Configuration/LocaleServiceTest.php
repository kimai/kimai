<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\LocaleService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\LocaleService
 */
class LocaleServiceTest extends TestCase
{
    protected function getSut(array $settings): LocaleService
    {
        return new LocaleService($settings);
    }

    /**
     * @return array<string, array{'date': string, 'time': string, 'rtl': bool, 'translation': bool}>
     */
    protected function getDefaultSettings(): array
    {
        return [
            'de' => [
                'date' => 'd.m.Y',
                'time' => 'H:i',
                'rtl' => false,
                'translation' => true,
            ],
            'en' => [
                'date' => 'Y-m-d',
                'time' => 'H:i:s',
                'rtl' => false,
                'translation' => true,
            ],
            'en_AU' => [
                'date' => 'Y-m-d',
                'time' => 'H:i:s',
                'rtl' => false,
                'translation' => false,
            ],
            'pt_BR' => [
                'date' => 'd-m-Y',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
            'it' => [
                'date' => 'd.m.Y',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
            'fr' => [
                'date' => 'd/m/Y',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
            'fr_BE' => [
                'date' => 'd/MM/yy',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => false,
            ],
            'fr_CA' => [
                'date' => 'y-MM-dd',
                'time' => 'HH \'h\' mm',
                'rtl' => false,
                'translation' => true,
            ],
            'es' => [
                'date' => 'd.m.Y',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
            'ru' => [
                'date' => 'd.m.Y',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
            'ar' => [
                'date' => 'Y-m-d',
                'time' => 'HH:mm',
                'rtl' => true,
                'translation' => true,
            ],
            'hu' => [
                'date' => 'Y.m.d.',
                'time' => 'HH:mm',
                'rtl' => false,
                'translation' => true,
            ],
        ];
    }

    public function testGetAllLocales(): void
    {
        $sut = $this->getSut([]);
        self::assertEquals([], $sut->getAllLocales());

        $sut = $this->getSut($this->getDefaultSettings());
        self::assertEquals(['de', 'en', 'en_AU', 'pt_BR', 'it', 'fr', 'fr_BE', 'fr_CA', 'es', 'ru', 'ar', 'hu'], $sut->getAllLocales());
    }

    public function testInvalidLocaleWithGivenLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown locale given: xx');

        $sut = $this->getSut($this->getDefaultSettings());
        $sut->getDateFormat('xx');
    }

    public function testGetDurationFormat(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertEquals('%h:%m', $sut->getDurationFormat('de'));
    }

    public function testGetDateFormat(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertEquals('d.m.Y', $sut->getDateFormat('de'));
    }

    public function testGetDateTimeFormat(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertEquals('d.m.Y H:i', $sut->getDateTimeFormat('de'));
    }

    public function testGetTimeFormat(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertEquals('H:i', $sut->getTimeFormat('de'));
        self::assertEquals('H:i:s', $sut->getTimeFormat('en'));
    }

    /**
     * @dataProvider getNearestTranslationLocaleData
     */
    public function testGetNearestTranslationLocale(string $locale, string $expected): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $actual = $sut->getNearestTranslationLocale($locale);
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getNearestTranslationLocaleData(): array
    {
        return [
            ['de', 'de'], // registered and translated: use it
            ['pt_BR', 'pt_BR'], // registered and translated: use it
            ['fr_CA', 'fr_CA'], // registered and translated: use it
            ['fr_BE', 'fr'], // not translated, fallback to base locale
            ['fr_DZ', 'fr'], // not registered region locale, but fallback to base locale
            ['en_AU', 'en'], // not translated, fallback to base locale
            ['uk_UA', 'en'], // not registered locales fallback to en
        ];
    }
}
