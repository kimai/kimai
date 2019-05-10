<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Configuration\LanguageFormattings;
use App\Utils\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\Utils\LocaleSettings
 * @covers \App\Configuration\LanguageFormattings
 */
class LocaleSettingsTest extends TestCase
{
    protected function getRequestStack(string $locale)
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    protected function getSut(string $locale, array $settings)
    {
        return new LocaleSettings($this->getRequestStack($locale), new LanguageFormattings($settings));
    }

    protected function getDefaultSettings()
    {
        return [
            'de' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date' => 'd.m.Y',
                'date_time' => 'd.m. H:i',
                'duration' => '%h:%m h',
                'time' => 'H:i',
                '24_hours' => true,
            ],
            'en' => [
                'date_time_type' => 'yyyy-MM-dd HH:mm',
                'date_type' => 'yyyy-MM-dd',
                'date' => 'Y-m-d',
                'date_time' => 'm-d H:i',
                'duration' => '%h:%m h',
                'time' => 'H:i:s',
                '24_hours' => false,
            ],
            'pt_BR' => [
                'date_time_type' => 'dd-MM-yyyy HH:mm',
                'date_type' => 'dd-MM-yyyy',
                'date' => 'd-m-Y',
                'duration' => '%h:%m h',
            ],
            'it' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'fr' => [
                'date_time_type' => 'dd/MM/yyyy HH:mm',
                'date_type' => 'dd/MM/yyyy',
                'date' => 'd/m/Y',
                'duration' => '%h h %m',
            ],
            'es' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'ru' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'ar' => [
                'date_time_type' => 'yyyy-MM-dd HH:mm',
                'date_type' => 'yyyy-MM-dd',
                'date' => 'Y-m-d',
                'duration' => '%h:%m h',
            ],
            'hu' => [
                'date_time_type' => 'yyyy.MM.dd HH:mm',
                'date_type' => 'yyyy.MM.dd',
                'date' => 'Y.m.d.',
                'duration' => '%h:%m h',
            ],
        ];
    }

    public function testGetLocale()
    {
        $sut = $this->getSut('en', []);
        $this->assertEquals('en', $sut->getLocale());
        $sut = $this->getSut('ar', []);
        $this->assertEquals('ar', $sut->getLocale());
    }

    public function testGetAvailableLanguages()
    {
        $sut = $this->getSut('en', []);
        $this->assertEquals([], $sut->getAvailableLanguages());

        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals(['de', 'en', 'pt_BR', 'it', 'fr', 'es', 'ru', 'ar', 'hu'], $sut->getAvailableLanguages());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidLocaleWithDefaultLocale()
    {
        $sut = $this->getSut('en', []);
        $sut->getDateFormat();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown locale given: xx
     */
    public function testInvalidLocaleWithGivenLocale()
    {
        $sut = $this->getSut('xx', $this->getDefaultSettings());
        $sut->getDateFormat();
    }

    public function testGetDurationFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('%h:%m h', $sut->getDurationFormat());
    }

    public function testGetDateFormat()
    {
        $sut = $this->getSut('de', $this->getDefaultSettings());
        $this->assertEquals('d.m.Y', $sut->getDateFormat());
    }

    public function testGetDateTimeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('m-d H:i', $sut->getDateTimeFormat());
    }

    public function testGetDateTypeFormat()
    {
        $sut = $this->getSut('de', $this->getDefaultSettings());
        $this->assertEquals('dd.MM.yyyy', $sut->getDateTypeFormat());
    }

    public function testGetDatePickerFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('YYYY-MM-DD', $sut->getDatePickerFormat());
    }

    public function testGetDateTimeTypeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('yyyy-MM-dd HH:mm', $sut->getDateTimeTypeFormat());
    }

    public function testGetDateTimePickerFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('YYYY-MM-DD HH:mm', $sut->getDateTimePickerFormat());
    }

    public function testIs24Hours()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertFalse($sut->isTwentyFourHours());
    }

    public function testGetTimeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('H:i:s', $sut->getTimeFormat());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown setting for locale en: date_time_type
     */
    public function testUnknownSetting()
    {
        $sut = $this->getSut('en', ['en' => [
            'xxx' => 'dd.MM.yyyy HH:mm',
        ]]);
        $sut->getDateTimePickerFormat();
    }
}
