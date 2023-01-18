<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Configuration\LocaleService;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Twig\LocaleFormatExtensions;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @covers \App\Twig\LocaleFormatExtensions
 * @covers \App\Utils\LocaleFormatter
 */
class LocaleFormatExtensionsTest extends TestCase
{
    private array $localeEn = ['en' => ['date' => 'Y-m-d', 'duration' => '%h:%m', 'time' => 'h:mm a']];
    private array $localeDe = ['de' => ['date' => 'd.m.Y', 'duration' => '%h:%m', 'time' => 'HH:mm']];
    private array $localeFake = ['XX' => ['date' => 'd.m.Y', 'duration' => '%h - %m - %s Zeit', 'time' => 'HH:mm']];

    private ?string $oldTimezone = null;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Vienna');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimezone);
        $this->oldTimezone = null;
    }

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
        $user->setTimezone('Europe/Vienna');
        $security = $this->createMock(Security::class);
        $security->expects($this->any())->method('getUser')->willReturn($user);

        $sut = new LocaleFormatExtensions(new LocaleService($dateSettings), $security);
        $sut->setLocale($language);

        return $sut;
    }

    public function testGetFilters(): void
    {
        $filters = [
            'month_name',
            'day_name',
            'date_short',
            'date_time',
            'date_full',
            'date_format',
            'date_weekday',
            'time',
            'duration',
            'chart_duration',
            'chart_money',
            'duration_decimal',
            'money',
            'amount',
            'js_format',
            'pattern',
        ];
        $i = 0;

        $sut = $this->getSut('de', []);
        $twigFilters = $sut->getFilters();
        //$this->assertCount(\count($filters), $twigFilters);

        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions(): void
    {
        $functions = ['javascript_configurations', 'create_date', 'month_names', 'locale_format'];
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

    public function testGetTests(): void
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
     * @dataProvider getDateShortData
     */
    public function testDateShort(string $locale, \DateTime|string|null $date, string $expected): void
    {
        $sut = $this->getSut($locale, [
            'de' => ['date' => 'dd.MM.Y'],
            'en' => ['date' => 'Y-MM-dd'],
            'ru' => ['date' => 'dd.MM.Y'],
        ]);
        $this->assertEquals($expected, $sut->dateShort($date));
    }

    /**
     * @return array<int, array<int, \DateTime|string|null>>
     * @throws \Exception
     */
    public function getDateShortData(): array
    {
        $timezone = new \DateTimeZone('Europe/Vienna');

        return [
            ['en', new \DateTime('7 January 2010 12:00:00', $timezone), '2010-01-07'],
            ['en', new \DateTime('2016-06-23 12:00:00', $timezone), '2016-06-23'],
            ['de', new \DateTime('1980-12-14 12:00:00', $timezone), '14.12.1980'],
            ['ru', new \DateTime('1980-12-14 12:00:00', $timezone), '14.12.1980'],
            ['ru', '1980-12-14 12:00:00', '14.12.1980'],
            ['ru', null, ''],
            ['ru', '', ''],
        ];
    }

    /**
     * @dataProvider getDateTimeData
     */
    public function testDateTime(string $locale, \DateTime|string|null $date, string $expected): void
    {
        $sut = $this->getSut($locale, [
            'de' => ['date' => 'dd.MM.Y', 'time' => 'HH:mm:s'],
            'en' => ['date' => 'Y-MM-dd', 'time' => 'h:mm a'],
        ]);
        $this->assertEquals($expected, $sut->dateTime($date));
    }

    /**
     * @return array<int, array<int, \DateTime|string|null>>
     * @throws \Exception
     */
    public function getDateTimeData(): array
    {
        $timezone = new \DateTimeZone('Europe/Vienna');

        return [
            ['en', new \DateTime('7 January 2010 00:01:00', $timezone), '2010-01-07 12:01 AM'],
            ['de', (new \DateTime('1980-12-14', $timezone))->setTime(13, 27, 55), '14.12.1980 13:27:55'],
            ['de', '1980-12-14 13:27:55', '14.12.1980 13:27:55'],
            ['de', null, ''],
            ['de', '', ''],
        ];
    }

    /**
     * @dataProvider getDayNameTestData
     */
    public function testDayName(string $locale, string $date, string $expectedName, bool $short): void
    {
        $sut = $this->getSut($locale, []);
        self::assertEquals($expectedName, $sut->dayName(new \DateTime($date), $short));
    }

    /**
     * @return array<int, array<int, string|bool>>
     * @throws \Exception
     */
    public function getDayNameTestData(): array
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
    public function testMonthName(string $locale, string $date, string $expectedName, bool $withYear = false): void
    {
        $sut = $this->getSut($locale, []);
        self::assertEquals($expectedName, $sut->monthName(new \DateTime($date), $withYear));
    }

    /**
     * @return array<int, array<int, string|bool>>
     * @throws \Exception
     */
    public function getMonthNameTestData(): array
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

    public function testDateFormat(): void
    {
        $date = new \DateTime('7 January 2010 17:43:21', new \DateTimeZone('Europe/Berlin'));
        $sut = $this->getSut('en', []);
        $this->assertEquals('2010-01-07T17:43:21+01:00', $sut->dateFormat($date, 'c'));
        $this->assertStringStartsWith('2010-01-07T17:43:21', $sut->dateFormat('7 January 2010 17:43:21', 'c'));
    }

    public function testTime(): void
    {
        $time = new \DateTime('2016-06-23');
        $time->setTime(17, 53, 23);

        $sut = $this->getSut('en', ['en' => ['time' => 'HH:mm']]);
        $this->assertEquals('17:53', $sut->time($time));
        $this->assertEquals('17:53', $sut->time('2016-06-23 17:53'));
    }

    public function testCreateDate(): void
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

    public function testMoneyWithoutCurrency(): void
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
    public function testMoneyNoCurrency($result, $amount, $currency, $locale): void
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency, false));
    }

    /**
     * @return array<int, array<int, string|null|int|float>>
     * @throws \Exception
     */
    public function getMoneyNoCurrencyData(): array
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
            ["13\u{a0}933,49", 13933.49, 'JPY', 'ru'],
            ['13,75', 13.75, 'CNY', 'de'],
            ['13.933,00', 13933, 'CNY', 'de'],
            ["13\u{a0}933,00", 13933, 'CNY', 'ru'],
            ['13,933.00', 13933, 'CNY', 'en'],
            ['13,933.00', 13933, 'CNY', 'zh_CN'],
            ['1.234.567,89', 1234567.891234567890000, 'USD', 'de'],
        ];
    }

    /**
     * @dataProvider getMoneyData
     */
    public function testMoney(string $result, float|int|null $amount, string $currency, string $locale): void
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency));
    }

    /**
     * @return array<int, array<int, null|string|float|int>>
     * @throws \Exception
     */
    public function getMoneyData(): array
    {
        return [
            ["0,00\u{a0}€", null, 'EUR', 'de'],
            ["2.345,00\u{a0}€", 2345, 'EUR', 'de'],
            ['€2,345.00', 2345, 'EUR', 'en'],
            ['€2,345.01', 2345.009, 'EUR', 'en'],
            ["2.345,01\u{a0}€", 2345.009, 'EUR', 'de'],
            ['$13.75', 13.75, 'USD', 'en'],
            ["13,75\u{a0}\$", 13.75, 'USD', 'de'],
            ["13,75\u{a0}RUB", 13.75, 'RUB', 'de'],
            ["14\u{a0}¥", 13.75, 'JPY', 'de'],
            ["13\u{a0}933\u{a0}¥", 13933.49, 'JPY', 'ru'],
            ["13,75\u{a0}CN¥", 13.75, 'CNY', 'de'],
            ["13.933,00\u{a0}CN¥", 13933, 'CNY', 'de'],
            ["13\u{a0}933,00\u{a0}CN¥", 13933, 'CNY', 'ru'],
            ['CN¥13,933.00', 13933, 'CNY', 'en'],
            ["1.234.567,89\u{a0}\$", 1234567.891234567890000, 'USD', 'de'],
        ];
    }

    /**
     * @dataProvider getAmountData
     */
    public function testAmount($result, $amount, $locale): void
    {
        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->amount($amount));
    }

    /**
     * @return array<int, array<int, null|int|string|float>>
     * @throws \Exception
     */
    public function getAmountData(): array
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
            ["13\u{a0}933,49", 13933.49, 'ru'],
            ['1.234.567,891', 1234567.891234567890000, 'de'],
        ];
    }

    /**
     * @dataProvider getMoneyData62_1
     */
    public function testMoney62_1($result, $amount, $currency, $locale): void
    {
        IntlTestHelper::requireFullIntl($this, '62.1');

        $sut = $this->getSut($this->localeEn, $locale);
        $this->assertEquals($result, $sut->money($amount, $currency));
    }

    /**
     * @return array<int, array<int, string|float>>
     * @throws \Exception
     */
    public function getMoneyData62_1(): array
    {
        return [
            ["RUB\u{a0}13.50", 13.50, 'RUB', 'en'],
            ["13,75\u{a0}₽", 13.75, 'RUB', 'ru'],
        ];
    }

    public function testDuration(): void
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut('en', $this->localeEn);
        $this->assertEquals('2:37', $sut->duration($record->getDuration()));
        $this->assertEquals('2.62', $sut->duration($record->getDuration(), true));

        // test Timesheet object
        $this->assertEquals('2:37', $sut->duration($record));
        $this->assertEquals('2.62', $sut->duration($record, true));

        // test extended format
        $sut = $this->getSut($this->localeFake, 'XX');
        $this->assertEquals('2:37', $sut->duration($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0:00', $sut->duration(0));
        $this->assertEquals('0:00', $sut->duration(-1));
        $this->assertEquals('0:00', $sut->duration(-59));
        $this->assertEquals('-0:01', $sut->duration(-60));
        $this->assertEquals('-1:36', $sut->duration(-5786));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0:00', $sut->duration(0));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('0:00', $sut->duration(null));
        $this->assertEquals('0.00', $sut->duration(null, true));
    }

    public function testDurationChart(): void
    {
        $sut = $this->getSut('en', $this->localeEn);
        self::assertEquals(0.34, $sut->durationChart(1234));
        self::assertEquals(3.43, $sut->durationChart(12345));
        self::assertEquals(34.29, $sut->durationChart(123456));
        self::assertEquals(342.94, $sut->durationChart(1234567));
    }

    public function testJavascriptConfigurations(): void
    {
        $expected = [
            'formatDuration' => '%h:%m',
            'formatDate' => 'Y-m-d',
            'defaultColor' => '#d2d6de',
            'twentyFourHours' => false,
            'updateBrowserTitle' => false,
            'timezone' => 'America/Edmonton',
        ];
        $user = new User();
        $user->setTimezone('America/Edmonton');
        $sut = $this->getSut('en', $this->localeEn);
        self::assertEquals($expected, $sut->getJavascriptConfiguration($user));
    }

    public function testDurationDecimal(): void
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
        $this->assertEquals('-0.01', $sut->durationDecimal(-40));
        $this->assertEquals('-0.01', $sut->durationDecimal(-50));

        // test negative duration - with rounding issue
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('-0.02', $sut->durationDecimal(-60));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0.00', $sut->durationDecimal(-0));
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

    public function testIsToday(): void
    {
        $sut = $this->getSut('en', $this->localeEn);
        $test = $this->getTest($sut, 'today');
        self::assertTrue(\call_user_func($test->getCallable(), new \DateTime()));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('-1 day')));
        self::assertFalse(\call_user_func($test->getCallable(), new \DateTime('+1 day')));
        self::assertFalse(\call_user_func($test->getCallable(), new \stdClass()));
        self::assertFalse(\call_user_func($test->getCallable(), null));
    }

    public function testIsWeekend(): void
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        self::assertFalse($sut->isWeekend(new \DateTime('first monday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first tuesday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first wednesday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first thursday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first friday this month')));
        self::assertTrue($sut->isWeekend(new \DateTime('first saturday this month')));
        self::assertTrue($sut->isWeekend(new \DateTime('first sunday this month')));
        self::assertFalse($sut->isWeekend(null));

        $sut = $this->getSut('en', $this->localeEn, true);
        self::assertFalse($sut->isWeekend(new \DateTime('first monday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first tuesday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first wednesday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first thursday this month')));
        self::assertTrue($sut->isWeekend(new \DateTime('first friday this month')));
        self::assertTrue($sut->isWeekend(new \DateTime('first saturday this month')));
        self::assertFalse($sut->isWeekend(new \DateTime('first sunday this month')));
        self::assertFalse($sut->isWeekend(null));
    }

    protected function getTimesheet($seconds): Timesheet
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

    public function testChartMoney(): void
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        $this->assertEquals('-123456.78', $sut->moneyChart(-123456.78));
        $this->assertEquals('123456.78', $sut->moneyChart(123456.78));
        $this->assertEquals('123456.00', $sut->moneyChart(123456));
        $this->assertEquals('456.00', $sut->moneyChart(456));
    }

    public function testChartDuration(): void
    {
        $sut = $this->getSut('en', $this->localeEn, false);
        $this->assertEquals('34.29', $sut->durationChart(123456));
        $this->assertEquals('-34.29', $sut->durationChart(-123456));
    }
}
