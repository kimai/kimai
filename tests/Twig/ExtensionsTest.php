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
use App\Twig\Extensions;
use App\Utils\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\Extensions
 */
class ExtensionsTest extends TestCase
{
    private $localeEn = ['en' => ['date' => 'Y-m-d', 'duration' => '%h:%m h']];
    private $localeDe = ['de' => ['date' => 'd.m.Y', 'duration' => '%h:%m h']];
    private $localeRu = ['ru' => ['date' => 'd.m.Y', 'duration' => '%h:%m h']];
    private $localeFake = ['XX' => ['date' => 'd.m.Y', 'duration' => '%h - %m - %s Zeit']];

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

        $localeSettings = new LocaleSettings($requestStack, new LanguageFormattings($locales));

        return new Extensions($localeSettings);
    }

    public function testGetFilters()
    {
        $filters = ['duration', 'money', 'currency', 'country', 'icon', 'docu_link'];
        $sut = $this->getSut($this->localeDe);
        $twigFilters = $sut->getFilters();
        $this->assertCount(count($filters), $twigFilters);
        $i = 0;
        /** @var TwigFilter $filter */
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['locales', 'class_name'];
        $sut = $this->getSut($this->localeDe);
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(count($functions), $twigFunctions);
        $i = 0;
        /** @var TwigFunction $filter */
        foreach ($twigFunctions as $filter) {
            $this->assertInstanceOf(TwigFunction::class, $filter);
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

    public function testMoneyNull()
    {
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('123.75', $sut->money(123.75));

        $sut = $this->getSut($this->localeEn, 'de');
        $this->assertEquals('123.234,755', $sut->money(123234.7554));
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
            ['1.234.567,89 $', 1234567.891234567890000, 'USD', 'de'],
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

        $sut = $this->getSut($this->localeEn);
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
        $this->assertEquals('02:37:17 h', $sut->duration($record->getDuration(), '%h:%m:%s h'));

        // test Timesheet object
        $this->assertEquals('02:37 h', $sut->duration($record));
        $this->assertEquals('02:37:17', $sut->duration($record, '%h:%m:%s'));

        // test extended format
        $sut = $this->getSut($this->localeFake, 'XX');
        $this->assertEquals('02 - 37 - 17 Zeit', $sut->duration($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('?', $sut->duration('-1'));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('00:00 h', $sut->duration('0'));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('00:00 h', $sut->duration(null));
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
            'create', 'list', 'print', 'visibility', 'calendar', 'money', 'duration', 'download', 'copy', 'settings',
            'export', 'pdf', 'csv', 'ods', 'xlsx', 'on', 'off', 'audit', 'home', 'shop', 'about', 'debug', 'profile-stats'
        ];

        // test pre-defined icons
        $sut = $this->getSut($this->localeEn);
        foreach ($icons as $icon) {
            $result = $sut->icon($icon);
            $this->assertNotEmpty($result, 'Problem with icon definition: ' . $icon);
            $this->assertIsString($result);
        }

        // test fallback will be returned
        $this->assertEquals('', $sut->icon('foo'));
        $this->assertEquals('bar', $sut->icon('foo', 'bar'));
    }

    public function testDocuLink()
    {
        $data = [
            'timesheet.html' => 'https://www.kimai.org/documentation/timesheet.html',
            'timesheet.html#duration-format' => 'https://www.kimai.org/documentation/timesheet.html#duration-format',
            'invoice.html' => 'https://www.kimai.org/documentation/invoice.html',
            '' => 'https://www.kimai.org/documentation/',
        ];

        $sut = $this->getSut($this->localeEn);
        foreach ($data as $input => $expected) {
            $result = $sut->documentationLink($input);
            $this->assertEquals($expected, $result);
        }
    }

    public function testGetClassName()
    {
        $sut = $this->getSut($this->localeEn);
        $this->assertEquals('DateTime', $sut->getClassName(new \DateTime()));
        $this->assertEquals('stdClass', $sut->getClassName(new \stdClass()));
        $this->assertNull($sut->getClassName(''));
        $this->assertNull($sut->getClassName(null));
        $this->assertEquals('App\Entity\User', $sut->getClassName(new User()));
    }
}
