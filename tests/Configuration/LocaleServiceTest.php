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
    protected function getSut(array $settings)
    {
        return new LocaleService($settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'de' => [
                'date' => 'd.m.Y',
                'duration' => '%h:%m',
                'time' => 'H:i',
            ],
            'en' => [
                'date' => 'Y-m-d',
                'duration' => '%h:%m',
                'time' => 'H:i:s',
            ],
            'pt_BR' => [
                'date' => 'd-m-Y',
                'duration' => '%h:%m',
            ],
            'it' => [
                'date' => 'd.m.Y',
                'duration' => '%h:%m',
            ],
            'fr' => [
                'date' => 'd/m/Y',
                'duration' => '%h h %m',
            ],
            'es' => [
                'date' => 'd.m.Y',
                'duration' => '%h:%m',
            ],
            'ru' => [
                'date' => 'd.m.Y',
                'duration' => '%h:%m',
            ],
            'ar' => [
                'date' => 'Y-m-d',
                'duration' => '%h:%m',
            ],
            'hu' => [
                'date' => 'Y.m.d.',
                'duration' => '%h:%m',
            ],
        ];
    }

    public function testGetAllLocales()
    {
        $sut = $this->getSut([]);
        $this->assertEquals([], $sut->getAllLocales());

        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals(['de', 'en', 'pt_BR', 'it', 'fr', 'es', 'ru', 'ar', 'hu'], $sut->getAllLocales());
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
        $this->assertEquals('%h:%m', $sut->getDurationFormat('de'));
    }

    public function testGetDateFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('d.m.Y', $sut->getDateFormat('de'));
    }

    public function testGetDateTimeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('d.m.Y H:i', $sut->getDateTimeFormat('de'));
    }

    public function testGetTimeFormat()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertEquals('H:i', $sut->getTimeFormat('de'));
        $this->assertEquals('H:i:s', $sut->getTimeFormat('en'));
    }
}
