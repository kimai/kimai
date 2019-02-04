<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\Utils\LocaleSettings
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
        return new LocaleSettings($this->getRequestStack($locale), $settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'de' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_time_picker' => 'DD.MM.YYYY HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date_picker' => 'DD.MM.YYYY',
                'date' => 'd.m.Y',
                'date_time' => 'd.m. H:i',
                'duration' => '%h:%m h',
            ],
            'en' => [
                'date_time_type' => 'yyyy-MM-dd HH:mm',
                'date_time_picker' => 'YYYY-MM-DD HH:mm',
                'date_type' => 'yyyy-MM-dd',
                'date_picker' => 'YYYY-MM-DD',
                'date' => 'Y-m-d',
                'date_time' => 'm-d H:i',
                'duration' => '%h:%m h',
            ],
            'pt_BR' => [
                'date_time_type' => 'dd-MM-yyyy HH:mm',
                'date_time_picker' => 'DD-MM-YYYY HH:mm',
                'date_type' => 'dd-MM-yyyy',
                'date_picker' => 'DD-MM-YYYY',
                'date' => 'd-m-Y',
                'duration' => '%h:%m h',
            ],
            'it' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_time_picker' => 'DD.MM.YYYY HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date_picker' => 'DD.MM.YYYY',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'fr' => [
                'date_time_type' => 'dd/MM/yyyy HH:mm',
                'date_time_picker' => 'DD/MM/YYYY HH:mm',
                'date_type' => 'dd/MM/yyyy',
                'date_picker' => 'DD/MM/YYYY',
                'date' => 'd/m/Y',
                'duration' => '%h h %m',
            ],
            'es' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_time_picker' => 'DD.MM.YYYY HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date_picker' => 'DD.MM.YYYY',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'ru' => [
                'date_time_type' => 'dd.MM.yyyy HH:mm',
                'date_time_picker' => 'DD.MM.YYYY HH:mm',
                'date_type' => 'dd.MM.yyyy',
                'date_picker' => 'DD.MM.YYYY',
                'date' => 'd.m.Y',
                'duration' => '%h:%m h',
            ],
            'ar' => [
                'date_time_type' => 'yyyy-MM-dd HH:mm',
                'date_time_picker' => 'YYYY-MM-DD HH:mm',
                'date_type' => 'yyyy-MM-dd',
                'date_picker' => 'YYYY-MM-DD',
                'date' => 'Y-m-d',
                'duration' => '%h:%m h',
            ],
            'hu' => [
                'date_time_type' => 'yyyy.MM.dd HH:mm',
                'date_time_picker' => 'YYYY.MM.DD HH:mm',
                'date_type' => 'yyyy.MM.dd',
                'date_picker' => 'YYYY.MM.DD',
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
     */
    public function testInvalidLocaleWithGivenLocale()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $sut->getDateFormat('xx');
    }

    public function testGetDurationFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('%h:%m h', $sut->getDurationFormat());
        $this->assertEquals('%h:%m h', $sut->getDurationFormat('de'));
    }

    public function testGetDateFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('Y-m-d', $sut->getDateFormat());
        $this->assertEquals('d.m.Y', $sut->getDateFormat('de'));
    }

    public function testGetDateTimeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('m-d H:i', $sut->getDateTimeFormat());
        $this->assertEquals('d.m. H:i', $sut->getDateTimeFormat('de'));
    }

    public function testGetDateTypeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('yyyy-MM-dd', $sut->getDateTypeFormat());
        $this->assertEquals('dd.MM.yyyy', $sut->getDateTypeFormat('de'));
    }

    public function testGetDatePickerFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('YYYY-MM-DD', $sut->getDatePickerFormat());
        $this->assertEquals('DD.MM.YYYY', $sut->getDatePickerFormat('de'));
    }

    public function testGetDateTimeTypeFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('yyyy-MM-dd HH:mm', $sut->getDateTimeTypeFormat());
        $this->assertEquals('dd.MM.yyyy HH:mm', $sut->getDateTimeTypeFormat('de'));
    }

    public function testGetDateTimePickerFormat()
    {
        $sut = $this->getSut('en', $this->getDefaultSettings());
        $this->assertEquals('YYYY-MM-DD HH:mm', $sut->getDateTimePickerFormat());
        $this->assertEquals('DD.MM.YYYY HH:mm', $sut->getDateTimePickerFormat('de'));
    }
}
