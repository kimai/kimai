<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\FormFormatConverter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\FormFormatConverter
 */
class FormFormatConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $sut = new FormFormatConverter();

        $this->assertEquals('dd.MM.yyyy', $sut->convert('dd.MM.yy'));
        $this->assertEquals('d.M.yyyy', $sut->convert('d.M.y'));
        $this->assertEquals('g:i A', $sut->convert('h:mm a'));
        $this->assertEquals('G:i', $sut->convert('H:mm'));
        $this->assertEquals('H.i', $sut->convert('HH.mm'));
        $this->assertEquals('A g:i', $sut->convert('a h:mm'));
        $this->assertEquals('H \h i', $sut->convert('HH \'h\' mm'));
    }

    /**
     * @dataProvider getProblemPattern
     */
    public function testProblemPattern($format, $example): void
    {
        $sut = new FormFormatConverter();
        $format = $sut->convert($format);
        $pattern = $sut->convertToPattern($format, false);
        $this->assertMatchesRegularExpression($pattern, $example);
    }

    public function testDayPattern(): void
    {
        for ($i = 1; $i < 32; $i++) {
            if ($i < 10) {
                $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_DAY_DOUBLE . '$/', '0' . $i);
            }
            $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_DAY_SINGLE . '$/', (string) $i);
        }
    }

    public function testHourPattern(): void
    {
        for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
                $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_HOUR_DOUBLE . '$/', '0' . $i);
            }
            $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_HOUR_SINGLE . '$/', (string) $i);
        }
    }

    public function testMinutePattern(): void
    {
        for ($i = 0; $i < 60; $i++) {
            if ($i < 10) {
                $i = '0' . $i;
            }
            $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_MINUTES . '$/', (string) $i);
        }
    }

    public function testMonthPattern(): void
    {
        for ($i = 1; $i < 13; $i++) {
            if ($i < 10) {
                $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_MONTH_DOUBLE . '$/', '0' . $i);
            }
            $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_MONTH_SINGLE . '$/', (string) $i);
        }
    }

    public function testYearPattern(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertMatchesRegularExpression('/^' . FormFormatConverter::PATTERN_YEAR . '$/', (string) (1900 + $i));
        }
    }

    public function getProblemPattern()
    {
        yield ["yy-MM-dd HH 'h' mm", '2009-08-06 17 h 45'];
    }

    public function testPattern(): void
    {
        $sut = new FormFormatConverter();
        foreach ($this->getPossibleDateTimePattern() as $format => $example) {
            $pattern = $sut->convertToPattern($format, false);
            $this->assertMatchesRegularExpression($pattern, $example, sprintf('Invalid pattern %s for format %s, did not match %s', $pattern, $format, $example));
        }
    }

    public function getPossibleDateTimePattern(): array
    {
        $timeFormat = [
          'ar' => 'h:mm a',
          'cs' => 'H:mm',
          'da' => 'HH.mm',
          'de' => 'HH:mm',
          'fi' => 'H.mm',
          'ko' => 'a h:mm',
          'fr_CA' => 'HH \'h\' mm',
        ];

        $timeExample = [
          'ar' => '8:13 AM',
          'cs' => '8:44',
          'da' => '08.44',
          'de' => '19:56',
          'fi' => '17.41',
          'ko' => 'AM 7:39',
          'fr_CA' => '17 h 45',
        ];

        $dateFormat = [
          //'ar' => 'd‏/M‏/y',
          'cs' => 'dd.MM.yy',
          'da' => 'dd.MM.y',
          'el' => 'd/M/yy',
          'en' => 'M/d/yy',
          'fi' => 'd.M.y',
          'fr' => 'dd/MM/y',
          'hu' => 'y. MM. dd.',
          'it' => 'dd/MM/yy',
          'nl' => 'dd-MM-y',
          'sk' => 'd. M. y',
          'sv' => 'y-MM-dd',
          'pl' => 'd.MM.y',
          'eo' => 'yy-MM-dd',
          'eu' => 'yy/M/d',
          'fa' => 'y/M/d',
          'hr' => 'dd. MM. y.',
          'ja' => 'y/MM/dd',
          'ko' => 'yy. M. d.',
          'en_HK' => 'd/M/y',
          'en_NZ' => 'd/MM/yy',
          'es_CL' => 'dd-MM-yy',
          'es_PA' => 'MM/dd/yy',
          'hr_BA' => 'd. M. yy.',
          'nl_BE' => 'd/MM/y',
        ];

        $dateExample = [
          'ar' => '6‏/8‏/2009',
          'cs' => '06.08.2009',
          'da' => '06.08.2009',
          'el' => '6/8/2009',
          'en' => '8/6/2009',
          'fi' => '6.8.2009',
          'fr' => '06/08/2009',
          'hu' => '2009. 08. 06.',
          'it' => '06/08/2009',
          'nl' => '06-08-2009',
          'sk' => '6. 8. 2009',
          'sv' => '2009-08-06',
          'pl' => '6.08.2009',
          'eo' => '2009-08-06',
          'eu' => '2009/8/6',
          'fa' => '2009/8/6',
          'hr' => '06. 08. 2009.',
          'ja' => '2009/08/06',
          'ko' => '2009. 8. 6.',
          'en_HK' => '6/8/2009',
          'en_NZ' => '6/08/2009',
          'es_CL' => '06-08-2009',
          'es_PA' => '08/06/2009',
          'hr_BA' => '6. 8. 2009.',
          'nl_BE' => '6/08/2009',
        ];

        $all = [];
        foreach ($dateFormat as $dateLocale => $date) {
            foreach ($timeFormat as $timeLocale => $time) {
                $all[$date . ' ' . $time] = $dateExample[$dateLocale] . ' ' . $timeExample[$timeLocale];
            }
        }

        return $all;
    }
}
