<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Configuration\LanguageFormattings;
use App\Twig\DateExtensions;
use App\Utils\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\DateExtensions
 */
class DateExtensionsTest extends TestCase
{
    /**
     * @param string $locale
     * @param array $dateSettings
     * @return DateExtensions
     */
    protected function getSut($locale, array $dateSettings)
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $localeSettings = new LocaleSettings($requestStack, new LanguageFormattings($dateSettings));

        return new DateExtensions($localeSettings);
    }

    public function testGetFilters()
    {
        $filters = ['month_name', 'date_short', 'date_time', 'date_full', 'date_format', 'time', 'hour24'];
        $sut = $this->getSut('de', []);
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);
        $i = 0;
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['get_format_duration'];
        $sut = $this->getSut('de', []);
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(TwigFunction::class, $filter);
            $this->assertEquals($functions[$i++], $filter->getName());
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
        return [
            ['en', new \DateTime('7 January 2010'), '2010-01-07'],
            ['en', new \DateTime('2016-06-23'), '2016-06-23'],
            ['de', new \DateTime('1980-12-14'), '14.12.1980'],
            ['ru', new \DateTime('1980-12-14'), '14.12.1980'],
            ['ru', '1980-12-14', '14.12.1980'],
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
        return [
            ['en', new \DateTime('7 January 2010'), '2010-01-07 12:01 AM'],
            ['de', (new \DateTime('1980-12-14'))->setTime(13, 27, 55), '14.12.1980 13:27:55'],
            ['de', '1980-12-14 13:27:55', '14.12.1980 13:27:55'],
        ];
    }

    /**
     * @param \DateTime $date
     * @param string $result
     * @dataProvider getMonthData
     */
    public function testMonthName(\DateTime $date, $result)
    {
        $sut = $this->getSut('en', []);
        $this->assertEquals($result, $sut->monthName($date));
    }

    public function getMonthData()
    {
        return [
            [new \DateTime('January 2016'), 'month.1'],
            [new \DateTime('2016-06-23'), 'month.6'],
            [new \DateTime('2016-12-23'), 'month.12'],
        ];
    }

    public function testDateFormat()
    {
        $date = new \DateTime('7 January 2010 17:43:21', new \DateTimeZone('Europe/Berlin'));
        $sut = $this->getSut('en', []);
        $this->assertEquals('2010-01-07T17:43:21+01:00', $sut->dateFormat($date, 'c'));
        $this->assertStringStartsWith('2010-01-07T17:43:21', $sut->dateFormat('7 January 2010 17:43:21', 'c'));
    }

    public function testTime()
    {
        $time = new \DateTime('2016-06-23');
        $time->setTime(17, 53, 23);

        $sut = $this->getSut('en', ['en' => ['time' => 'H:i']]);
        $this->assertEquals('17:53', $sut->time($time));
        $this->assertEquals('17:53', $sut->time('2016-06-23 17:53'));
    }

    public function testHour24()
    {
        $sut = $this->getSut('en', [
            'en' => ['24_hours' => false],
        ]);
        $this->assertEquals('bar', $sut->hour24('foo', 'bar'));

        $sut = $this->getSut('de', [
            'de' => ['24_hours' => true],
        ]);
        $this->assertEquals('foo', $sut->hour24('foo', 'bar'));
    }

    public function testDateTimeFull()
    {
        $sut = $this->getSut('en', [
            'en' => ['date_time_type' => 'yyyy-MM-dd HH:mm:ss'],
        ]);

        $dateTime = new \DateTime('2019-08-17 12:29:47', new \DateTimeZone(date_default_timezone_get()));
        $dateTime->setDate(2019, 8, 17);
        $dateTime->setTime(12, 29, 47);

        $this->assertEquals('2019-08-17 12:29:47', $sut->dateTimeFull($dateTime));
        $this->assertEquals('2019-08-17 12:29:47', $sut->dateTimeFull('2019-08-17 12:29:47'));
    }
}
