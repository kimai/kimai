<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\LanguageFormattings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\LanguageFormattings
 */
class LanguageFormattingsTest extends TestCase
{
    protected function getSut(array $settings)
    {
        return new LanguageFormattings($settings);
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

    public function testGetAvailableLanguages()
    {
        $sut = $this->getSut([]);
        $this->assertEquals([], $sut->getAvailableLanguages());

        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals(['de', 'en', 'pt_BR', 'it', 'fr', 'es', 'ru', 'ar', 'hu'], $sut->getAvailableLanguages());
    }

    public function testInvalidLocaleWithGivenLocale()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown locale given: xx');

        $sut = $this->getSut($this->getDefaultSettings());
        $sut->getDateFormat('xx');
    }

    public function testGetDurationFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('%h:%m h', $sut->getDurationFormat('de'));
    }

    public function testGetDateFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('d.m.Y', $sut->getDateFormat('de'));
    }

    public function testGetDateTimeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('d.m. H:i', $sut->getDateTimeFormat('de'));
    }

    public function testGetDateTypeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('dd.MM.yyyy', $sut->getDateTypeFormat('de'));
    }

    public function testGetDatePickerFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('DD.MM.YYYY', $sut->getDatePickerFormat('de'));
    }

    public function testGetDateTimeTypeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('dd.MM.yyyy HH:mm', $sut->getDateTimeTypeFormat('de'));
    }

    public function testGetDateTimePickerFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('DD.MM.YYYY HH:mm', $sut->getDateTimePickerFormat('de'));
    }

    public function testIs24Hours()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertTrue($sut->isTwentyFourHours('de'));
        $this->assertFalse($sut->isTwentyFourHours('en'));
    }

    public function testGetTimeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('H:i', $sut->getTimeFormat('de'));
        $this->assertEquals('H:i:s', $sut->getTimeFormat('en'));
    }

    public function testUnknownSetting()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown setting for locale en: date_time_type');

        $sut = $this->getSut(['en' => [
            'xxx' => 'dd.MM.yyyy HH:mm',
        ]]);
        $sut->getDateTimePickerFormat('en');
    }
}
