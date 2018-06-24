<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Entity\Timesheet;
use App\Twig\Extensions;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

/**
 * @covers \App\Twig\Extensions
 */
class ExtensionsTest extends TestCase
{
    protected function getSut($locales)
    {
        return new Extensions($locales);
    }

    public function testGetFilters()
    {
        $filters = ['duration', 'money', 'currency', 'country', 'month_name'];
        $sut = $this->getSut('de');
        $twigFilters = $sut->getFilters();
        $this->assertCount(count($filters), $twigFilters);
        $i = 0;
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['locales'];
        $sut = $this->getSut('de');
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(count($functions), $twigFunctions);
        $i = 0;
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(\Twig_SimpleFunction::class, $filter);
            $this->assertEquals($functions[$i++], $filter->getName());
        }
    }

    public function testLocales()
    {
        $locales = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'de', 'name' => 'Deutsch'],
            ['code' => 'ru', 'name' => 'русский'],
        ];

        $sut = $this->getSut('en|de|ru');
        $this->assertEquals($locales, $sut->getLocales());
    }

    public function testCurrency()
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'RUB' => 'RUB',
        ];

        $sut = $this->getSut('en');
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
        ];

        $sut = $this->getSut('en');
        foreach ($countries as $locale => $name) {
            $this->assertEquals($name, $sut->country($locale));
        }
    }

    /**
     * @param \DateTime $date
     * @param string $result
     * @dataProvider getMonthData
     */
    public function testMonthName(\DateTime $date, $result)
    {
        $sut = $this->getSut('en');
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

    public function testMoney()
    {
        $money = [
            [2222, 'EUR', '2,222.00 €'],
            [13.75, 'USD', '13.75 $'],
        ];

        $sut = $this->getSut('en');
        foreach ($money as $entry) {
            $amount = $entry[0];
            $currency = $entry[1];
            $expected = $entry[2];
            $this->assertEquals($expected, $sut->money($amount, $currency));
        }
    }

    public function testDuration()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut('en');
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
        $this->assertEquals('02:37:17 h', $sut->duration($record->getDuration(), true));

        $this->assertEquals('02:37 h', $sut->duration($record));
        $this->assertEquals('02:37:17 h', $sut->duration($record, true));
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
}
