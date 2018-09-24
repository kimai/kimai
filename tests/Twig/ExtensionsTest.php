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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;

/**
 * @covers \App\Twig\Extensions
 */
class ExtensionsTest extends TestCase
{
    private $localeEn = ['en' => ['date_short' => 'Y-m-d', 'duration' => '%h:%m:%s h', 'duration_short' => '%h:%m h']];
    private $localeDe = ['de' => ['date_short' => 'd.m.Y', 'duration' => '%h:%m:%s Stunden', 'duration_short' => '%h:%m h']];
    private $localeRu = ['ru' => ['date_short' => 'd.m.Y', 'duration' => '%h:%m:%s h', 'duration_short' => '%h:%m h']];
    private $localeFake = ['XX' => ['date_short' => 'd.m.Y', 'duration' => '%h Stunden, %m Minuten und %s Sekunden', 'duration_short' => '%h - %m - %s Zeit']];

    /**
     * @param array $locales
     * @param string $locale
     * @return Extensions
     */
    protected function getSut($locales, $locale = 'en')
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new Extensions($requestStack, $locales);
    }

    public function testGetFilters()
    {
        $filters = ['duration', 'money', 'currency', 'country', 'icon'];
        $sut = $this->getSut($this->localeDe);
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
        $functions = ['locales', 'is_visible_column'];
        $sut = $this->getSut($this->localeDe);
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

        $appLocales = array_merge($this->localeEn, $this->localeDe, $this->localeRu);
        $sut = $this->getSut($appLocales);
        $this->assertEquals($locales, $sut->getLocales());
    }

    public function testCurrency()
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'RUB' => 'RUB',
        ];

        $sut = $this->getSut($this->localeEn);
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

        $sut = $this->getSut($this->localeEn);
        foreach ($countries as $locale => $name) {
            $this->assertEquals($name, $sut->country($locale));
        }
    }

    /**
     * @param string $result
     * @param int $amount
     * @param string $currency
     * @param string $locale
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
            ['0 €', null, 'EUR', 'de'],
            ['2.345 €', 2345, 'EUR', 'de'],
            ['2,345 €', 2345, 'EUR', 'en'],
            ['2,345.01 €', 2345.009, 'EUR', 'en'],
            ['2.345,01 €', 2345.009, 'EUR', 'de'],
            ['13.75 $', 13.75, 'USD', 'en'],
            ['13,75 $', 13.75, 'USD', 'de'],
            ['13,75 RUB', 13.75, 'RUB', 'de'],
            ['13,5 RUB', 13.50, 'RUB', 'de'],
            ['13,75 ₽', 13.75, 'RUB', 'ru'],
            ['14 ¥', 13.75, 'JPY', 'de'],
            ['13 933 ¥', 13933.49, 'JPY', 'ru'],
            ['1.234.567,89 $', 1234567.891234567890000, 'USD', 'de'],
        ];
    }

    public function testDuration()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut($this->localeEn);
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
        $this->assertEquals('02:37:17 h', $sut->duration($record->getDuration(), '%h:%m:%s h'));

        // test Timesheet object
        $this->assertEquals('02:37 h', $sut->duration($record));
        $this->assertEquals('02:37:17', $sut->duration($record, '%h:%m:%s'));

        // test extended format
        $sut = $this->getSut($this->localeFake, 'XX');
        $this->assertEquals('02 - 37 - 17 Zeit', $sut->duration($record->getDuration(), 'short'));
        $this->assertEquals('02 Stunden, 37 Minuten und 17 Sekunden', $sut->duration($record->getDuration(), 'full'));

        // test fallback format
        $sut = $this->getSut($this->localeEn, 'XX');
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
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

    public function testIcon()
    {
        $icons = [
            'user', 'customer', 'project', 'activity', 'admin', 'invoice', 'timesheet', 'dashboard', 'logout', 'trash',
            'delete', 'repeat', 'edit', 'manual', 'help', 'start', 'start-small', 'stop', 'stop-small', 'filter',
            'create', 'list', 'print', 'visibility', 'calendar', 'money', 'duration', 'download'
        ];

        // test pre-defined icons
        $sut = $this->getSut($this->localeEn);
        foreach ($icons as $icon) {
            $result = $sut->icon($icon);
            $this->assertNotEmpty($result, 'Problem with icon definition: ' . $icon);
            $this->assertInternalType('string', $result);
        }

        // test fallback will be returned
        $this->assertEquals('', $sut->icon('foo'));
        $this->assertEquals('bar', $sut->icon('foo', 'bar'));
    }
}
