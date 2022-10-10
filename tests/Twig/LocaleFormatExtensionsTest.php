<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Configuration\LanguageFormattings;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Twig\LocaleFormatExtensions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Security\Core\Security;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @covers \App\Twig\LocaleFormatExtensions
 * @covers \App\Utils\LocaleFormatter
 * @covers \App\Utils\LocaleFormats
 */
class LocaleFormatExtensionsTest extends TestCase
{
    private $localeEn = ['en' => ['date' => 'Y-m-d', 'duration' => '%h:%m h', 'date_type' => 'yyyy-MM-dd']];
    private $localeDe = ['de' => ['date' => 'd.m.Y', 'duration' => '%h:%m h', 'date_type' => 'yyyy-MM-dd']];
    private $localeRu = ['ru' => ['date' => 'd.m.Y', 'duration' => '%h:%m h', 'date_type' => 'yyyy-MM-dd']];
    private $localeFake = ['XX' => ['date' => 'd.m.Y', 'duration' => '%h - %m Zeit', 'date_type' => 'yyyy-MM-dd']];

    /**
     * @param string|array $locale
     * @param array|string $dateSettings
     * @param bool $fdowSunday
     * @return LocaleFormatExtensions
     */
    protected function getSut($locale, $dateSettings, $fdowSunday = false)
    {
        $language = $locale;
        if (\is_array($locale)) {
            $language = $dateSettings;
            $dateSettings = $locale;
        }

        $user = new User();
        $user->setPreferenceValue(UserPreference::FIRST_WEEKDAY, ($fdowSunday ? 'sunday' : 'monday'));
        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn($user);

        $sut = new LocaleFormatExtensions(new LanguageFormattings($dateSettings), $security);
        $sut->setLocale($language);

        return $sut;
    }

    public function testGetFilters()
    {
        $filters = [
            'month_name', 'day_name', 'date_short', 'date_time', 'date_full', 'date_format', 'date_weekday', 'time', 'hour24',
            'duration', 'chart_duration', 'chart_money', 'duration_decimal', 'money', 'currency', 'country', 'language', 'amount'
        ];
        $i = 0;

        $sut = $this->getSut('de', []);
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);

        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['javascript_configurations', 'get_format_duration', 'create_date', 'locales', 'month_names'];
        $i = 0;

        $sut = $this->getSut('de', []);
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);

        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(TwigFunction::class, $filter);
            $this->assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testGetTests()
    {
        $tests = ['weekend', 'today'];
        $i = 0;

        $sut = $this->getSut('de', []);
        $twigTests = $sut->getTests();
        $this->assertCount(\count($tests), $twigTests);

        /** @var TwigTest $test */
        foreach ($twigTests as $test) {
            $this->assertInstanceOf(TwigTest::class, $test);
            $this->assertEquals($tests[$i++], $test->getName());
        }
    }

    /**
     * @param string $locale
     * @param \DateTime|string $date
     * @param string $result
     * @dataProvider getDateShortData
     */
    public function testDateShort($locale, $date, $result)
    {
        $sut = $this->getSut($locale, [
            'de' => ['date' => 'd.m.Y'],
            'en' => ['date' => 'Y-m-d'],
            'ru' => ['date' => 'd.m.Y'],
        ]);
        $this->assertEquals($result, $sut->dateShort($date));
    }

    public function getDateShortData()
    {
        $timezone = new \DateTimeZone('Europe/Vienna');

        return [
            ['en', new \DateTime('7 January 2010', $timezone), '2010-01-07'],
            ['en', new \DateTime('2016-06-23', $timezone), '2016-06-23'],
            ['de', new \DateTime('1980-12-14', $timezone), '14.12.1980'],
            ['ru', new \DateTime('1980-12-14', $timezone), '14.12.1980'],
            ['ru', '1980-12-14', '14.12.1980'],
            ['ru', 1.2345, 1.2345],
        ];
    }

    /**
     * @param string $locale
     * @param \DateTime|string $date
     * @param string $result
     * @dataProvider getDateTimeData
     */
    public function testDateTime($locale, $date, $result)
    {
        $sut = $this->getSut($locale, [
            'de' => ['date_time' => 'd.m.Y H:i:s'],
            'en' => ['date_time' => 'Y-m-d h:m A'],
        ]);
        $this->assertEquals($result, $sut->dateTime($date));
    }

    public function getDateTimeData()
    {
        $timezone = new \DateTimeZone('Europe/Vienna');

        return [
            ['en', new \DateTime('7 January 2010', $timezone), '2010-01-07 12:01 AM'],
            ['de', (new \DateTime('1980-12-14', $timezone))->setTime(13, 27, 55), '14.12.1980 13:27:55'],
            ['de', '1980-12-14 13:27:55', '14.12.1980 13:27:55'],
            ['de', 1.2345, 1.2345],
        ];
    }

    /**
     * @dataProvider getDayNameTestData
     */
    public function testDayName(string $locale, string $date, string $expectedName, bool $short)
    {
        $sut = $this->getSut($locale, []);
        self::assertEquals($expectedName, $sut->dayName(new \DateTime($date), $short));
    }

    public function getDayNameTestData()
    {
        return [
            ['de', '2020-07-09 12:00:00', 'Donnerstag', false],
            ['en', '2020-07-09 12:00:00', 'Thursday', false],
            ['de', '2020-07-09 12:00:00', 'Do.', true],
            ['en', '2020-07-09 12:00:00', 'Thu', true],
        ];
    }

    /**
     * @dataProvider getMonthNameTestData
     */
    public function testMonthName(string $locale, string $date, string $expectedName, bool $withYear = false)
    {
        $sut = $this->getSut($locale, []);
        self::assertEquals($expectedName, $sut->monthName(new \DateTime($date), $withYear));
    }

    public function getMonthNameTestData()
    {
        return [
            ['de', '2020-07-09 23:59:59', 'Juli', false],
            ['en', '2020-07-09 23:59:59', 'July', false],
            ['de', 'January 2016', 'Januar', false],
            ['en', 'January 2016', 'January', false],
            ['en', '2016-12-23', 'December', false],
            ['ru', '2016-12-23', 'декабрь', false],
            ['de', '2020-07-09 23:59:59', 'Juli 2020', true],
            ['en', '2020-07-09 23:59:59', 'July 2020', true],
            ['de', 'January 2016', 'Januar 2016', true],
            ['en', 'January 2016', 'January 2016', true],
            ['en', '2015-12-23', 'December 2015', true],
            ['ru', '2015-12-23', 'декабрь 2015', true],
        ];
    }

    public function testDateFormat()
    {
        $date = new \DateTime('7 January 2010 17:43:21', new \DateTimeZone('Europe/Berlin'));
        $sut = $this->getSut('en', []);
        $this->assertEquals('2010-01-07T17:43:21+01:00', $sut->dateFormat($date, 'c'));
        $this->assertStringStartsWith('2010-01-07T17:43:21', $sut->dateFormat('7 January 2010 17:43:21', 'c'));

        // next test checks the fallback for errors while converting the date
        /* @phpstan-ignore-next-line */
        $this->assertEquals(2010.0107, $sut->dateFormat(2010.0107, 'c'));
    }

    public function testTime()
    {
        $time = new \DateTime('2016-06-23');
        $time->setTime(17, 53, 23);

        $sut = $this->getSut('en', ['en' => ['time' => 'H:i']]);
        $this->assertEquals('17:53', $sut->time($time));
        $this->assertEquals('17:53', $sut->time('2016-06-23 17:53'));
    }

    public function testDateTimeFull()
    {
        $sut = $this->getSut('en', [
            'en' => ['date_type' => 'dd-yyyy-MM-'],
        ]);

        $dateTime = new \DateTime('2019-08-17 12:29:47', new \DateTimeZone(date_default_timezone_get()));
        $dateTime->setDate(2019, 8, 17);
        $dateTime->setTime(12, 29, 47);

        $this->assertEquals('17-2019-08- 12:29', $sut->dateTimeFull($dateTime));
        $this->assertEquals('17-2019-08- 12:29', $sut->dateTimeFull('2019-08-17 12:29:47'));

        $dateTime = new \DateTime('2019-08-17 00:00:00');
        $this->assertEquals('17-2019-08-', $sut->dateTimeFull($dateTime, true));

        // next test checks the fallback for errors while converting the date
        /* @phpstan-ignore-next-line */
        $this->assertEquals(189.45, $sut->dateTimeFull(189.45));
    }

    public function testCreateDate()
    {
        $user = new User();
        $user->setTimezone('Europe/Berlin');
        $sut = $this->getSut('en', []);
        $date = $sut->createDate('now', $user);
        $this->assertEquals('Europe/Berlin', $date->getTimezone()->getName());

        $user->setTimezone('Asia/Dubai');
        $date = $sut->createDate('2019-08-27 16:30:45', $user);
        $this->assertEquals('2019-08-27T16:30:45+0400', $date->format(DATE_ISO8601));
        $this->assertEquals('Asia/Dubai', $date->getTimezone()->getName());

        $date = $sut->createDate('2019-08-27 16:30:45', null);
        $this->assertEquals(date_default_timezone_get(), $date->getTimezone()->getName());
    }

    public function testLocales()
    {
        $locales = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'de', 'name' => 'Deutsch'],
            ['code' => 'ru', 'name' => 'русский'],
        ];

        $appLocales = array_merge($this->localeEn, $this->localeDe, $this->localeRu);
        $sut = $this->getSut('en', $appLocales);
        $this->assertEquals($locales, $sut->getLocales());
    }

    public function testCurrency()
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'RUB' => 'RUB',
            'rub' => 'RUB',
            123 => 123,
        ];

        $sut = $this->getSut('en', $this->localeEn);
        foreach ($symbols as $name => $symbol) {
            $this->assertEquals($symbol, $sut->currency($name));
        }
    }

    public function testCountry()
    {
        $countries = [
            'DE' => 'Germany',
            'RU' => 'Russia',
            'ES' => 'Spain',
            'es' => 'Spain',
            '12' => '12',
        ];

        $sut = $this->getSut('en', $this->localeEn);
        foreach ($countries as $locale => $name) {
            $this->assertEquals($name, $sut->country($locale));
        }
    }

    public function testLanguage()
    {
        $languages = [
            'de' => 'German',
            'ru' => 'Russian',
            'es' => 'Spanish',
            'ES' => 'Spanish',
            '12' => '12',
        ];

        $sut = $this->getSut('en', $this->localeEn);
        foreach ($languages as $locale => $name) {
            $this->assertEquals($name, $sut->language($locale));
        }
    }

    public function testMoneyWithoutCurrency()
    {
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('123.75', $sut->money(123.75));

        $sut = $this->getSut($this->localeEn, 'de');
        $this->assertEquals('123.234,76', $sut->money(123234.7554, null, true));
        $this->assertEquals('123.234,76', $sut->money(123234.7554, null, false));
        $this->assertEquals('123.234,76', $sut->money(123234.7554, 'EUR', false));
    }

    /**
     * @dataProvider getMoneyNoCurrencyData
     */
    public function testMoneyNoCurrency($result, $amount, $currency, $locale)
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency, false));
    }

    public function getMoneyNoCurrencyData()
    {
        return [
            ['0,00', null, 'EUR', 'de'],
            ['2.345,00', 2345, 'EUR', 'de'],
            ['2,345.00', 2345, 'EUR', 'en'],
            ['2,345.01', 2345.009, 'EUR', 'en'],
            ['2.345,01', 2345.009, 'EUR', 'de'],
            ['13.75', 13.75, 'USD', 'en'],
            ['13,75', 13.75, 'USD', 'de'],
            ['13,75', 13.75, 'RUB', 'de'],
            ['13,75', 13.75, 'JPY', 'de'],
            ['13 933,49', 13933.49, 'JPY', 'ru'],
            ['13,75', 13.75, 'CNY', 'de'],
            ['13.933,00', 13933, 'CNY', 'de'],
            ['13 933,00', 13933, 'CNY', 'ru'],
            ['13,933.00', 13933, 'CNY', 'en'],
            ['13,933.00', 13933, 'CNY', 'zh_CN'],
            ['1.234.567,89', 1234567.891234567890000, 'USD', 'de'],
        ];
    }

    /**
     * @dataProvider getMoneyData
     */
    public function testMoney($result, $amount, $currency, $locale)
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency));
    }

    public function getMoneyData()
    {
        return [
            ['0,00 €', null, 'EUR', 'de'],
            ['2.345,00 €', 2345, 'EUR', 'de'],
            ['€2,345.00', 2345, 'EUR', 'en'],
            ['€2,345.01', 2345.009, 'EUR', 'en'],
            ['2.345,01 €', 2345.009, 'EUR', 'de'],
            ['$13.75', 13.75, 'USD', 'en'],
            ['13,75 $', 13.75, 'USD', 'de'],
            ['13,75 RUB', 13.75, 'RUB', 'de'],
            ['14 ¥', 13.75, 'JPY', 'de'],
            ['13 933 ¥', 13933.49, 'JPY', 'ru'],
            ['13,75 CN¥', 13.75, 'CNY', 'de'],
            ['13.933,00 CN¥', 13933, 'CNY', 'de'],
            ['13 933,00 CN¥', 13933, 'CNY', 'ru'],
            ['CN¥13,933.00', 13933, 'CNY', 'en'],
            ['1.234.567,89 $', 1234567.891234567890000, 'USD', 'de'],
        ];
    }

    /**
     * @dataProvider getAmountData
     */
    public function testAmount($result, $amount, $locale)
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->amount($amount));
    }

    public function getAmountData()
    {
        return [
            ['0', null, 'de'],
            ['2.345,01', 2345.01, 'de'],
            ['2.345', 2345, 'de'],
            ['2,345', 2345, 'en'],
            ['2,345.009', 2345.009, 'en'],
            ['2.345,009', 2345.009, 'de'],
            ['13.75', 13.75, 'en'],
            ['13,75', 13.75, 'de'],
            ['13 933,49', 13933.49, 'ru'],
            ['1.234.567,891', 1234567.891234567890000, 'de'],
        ];
    }

    /**
     * @dataProvider getMoneyData62_1
     */
    public function testMoney62_1($result, $amount, $currency, $locale)
    {
        IntlTestHelper::requireFullIntl($this, '62.1');

        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency));
    }

    public function getMoneyData62_1()
    {
        return [
            ['RUB 13.50', 13.50, 'RUB', 'en'],
            ['13,75 ₽', 13.75, 'RUB', 'ru'],
        ];
    }

    public function testDuration()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut('en', $this->localeEn);
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
        $this->assertEquals('2.62', $sut->duration($record->getDuration(), true));

        // test Timesheet object
        $this->assertEquals('02:37 h', $sut->duration($record));
        $this->assertEquals('2.62', $sut->duration($record, true));

        // test extended format
        $sut = $this->getSut($this->localeFake, 'XX');
        $this->assertEquals('02 - 37 Zeit', $sut->duration($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('00:00 h', $sut->duration(0));
        $this->assertEquals('00:00 h', $sut->duration(-1));
        $this->assertEquals('00:00 h', $sut->duration(-59));
        $this->assertEquals('-00:01 h', $sut->duration(-60));
        $this->assertEquals('-01:36 h', $sut->duration(-5786));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('00:00 h', $sut->duration(0));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('00:00 h', $sut->duration(null));
        $this->assertEquals('0.00', $sut->duration(null, true));
    }

    public function testDurationChart()
    {
        $sut = $this->getSut('en', $this->localeEn);
        self::assertEquals(0.34, $sut->durationChart(1234));
        self::assertEquals(3.43, $sut->durationChart(12345));
        self::assertEquals(34.29, $sut->durationChart(123456));
        self::assertEquals(342.94, $sut->durationChart(1234567));
    }

    public function testJavascriptConfigurations()
    {
        $expected = [
            'formatDuration' => '%h:%m h',
            'formatDate' => 'YYYY-MM-DD',
            'defaultColor' => '#d2d6de',
            'twentyFourHours' => true,
            'updateBrowserTitle' => false,
        ];
        $sut = $this->getSut('en', $this->localeEn);
        self::assertEquals($expected, $sut->getJavascriptConfiguration(new User()));
    }

    public function testDurationDecimal()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut('en', $this->localeEn);
        $this->assertEquals('2.62', $sut->durationDecimal($record->getDuration()));

        // test Timesheet object
        $this->assertEquals('2.62', $sut->durationDecimal($record));

        // test extended format
        $sut = $this->getSut($this->localeDe, 'de');
        $this->assertEquals('2,62', $sut->durationDecimal($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('-0.00', $sut->durationDecimal(-1));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('-0.01', $sut->durationDecimal(-50));

        // test negative duration - with rounding issue
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('-0.02', $sut->durationDecimal(-60));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0.00', $sut->durationDecimal(0));

        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0.00', $sut->durationDecimal(-0));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('0.00', $sut->durationDecimal(null));
    }

    private function getTest(LocaleFormatExtensions $sut, string $name): TwigTest
    {
        foreach ($sut->getTests() as $test) {
            if ($test->getName() === $name) {
                return $test;
            }
        }

        throw new \Exception('Unknown twig test: ' . $name);
    }

    public function testIsToday()
    {
        $sut = $this->getSut('en', $this->localeEn);
        $test = $this->getTest($sut, 'today');
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime()));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('-1 day')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('+1 day')));
        self::assertFalse(\call_user_func($test->getCallable(), new \stdClass()));
        self::assertFalse(\call_user_func($test->getCallable(), null));
    }

    public function testIsWeekend()
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        $test = $this->getTest($sut, 'weekend');
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first monday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first tuesday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first wednesday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first thursday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first friday this month')));
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime('first saturday this month')));
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime('first sunday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \stdClass()));
        self::assertFalse(\call_user_func($test->getCallable(), null));

        $sut = $this->getSut('en', $this->localeEn, true);
        $test = $this->getTest($sut, 'weekend');
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first monday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first tuesday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first wednesday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first thursday this month')));
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime('first friday this month')));
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime('first saturday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('first sunday this month')));
        self::assertFalse(\call_user_func($test->getCallable(), new \stdClass()));
        self::assertFalse(\call_user_func($test->getCallable(), null));
    }

    protected function getTimesheet($seconds)
    {
        $begin = new \DateTime();
        $end = clone $begin;
        $end->setTimestamp($begin->getTimestamp() + $seconds);
        $record = new Timesheet();
        $record->setBegin($begin);
        $record->setEnd($end);
        $record->setDuration($seconds);

        return $record;
    }

    public function testChartMoney()
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        $this->assertEquals('-123456.78', $sut->moneyChart(-123456.78));
        $this->assertEquals('123456.78', $sut->moneyChart(123456.78));
        $this->assertEquals('123456.00', $sut->moneyChart(123456));
        $this->assertEquals('456.00', $sut->moneyChart(456));
    }

    public function testCharDuration()
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        $this->assertEquals('34.29', $sut->durationChart(123456.78));
        $this->assertEquals('-34.29', $sut->durationChart(-123456.78));
    }
}
