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
use App\Twig\LocaleExtensions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @covers \App\Twig\LocaleExtensions
 * @covers \App\Utils\LocaleFormatter
 */
class LocaleExtensionsTest extends TestCase
{
    private $localeEn = ['en' => ['date' => 'Y-m-d', 'duration' => '%h:%m h']];
    private $localeDe = ['de' => ['date' => 'd.m.Y', 'duration' => '%h:%m h']];
    private $localeRu = ['ru' => ['date' => 'd.m.Y', 'duration' => '%h:%m h']];
    private $localeFake = ['XX' => ['date' => 'd.m.Y', 'duration' => '%h - %m - %s Zeit']];

    /**
     * @param array $locales
     * @param string $locale
     * @return LocaleExtensions
     */
    protected function getSut($locales, $locale = 'en')
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new LocaleExtensions($requestStack, new LanguageFormattings($locales));
    }

    public function testGetFilters()
    {
        $filters = ['duration', 'duration_decimal', 'money', 'currency', 'country', 'language', 'amount'];
        $sut = $this->getSut($this->localeDe);
        $twigFilters = $sut->getFilters();
        $this->assertCount(\count($filters), $twigFilters);
        $i = 0;
        /** @var TwigFilter $filter */
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    public function testGetFunctions()
    {
        $functions = ['locales'];
        $sut = $this->getSut($this->localeDe);
        $twigFunctions = $sut->getFunctions();
        $this->assertCount(\count($functions), $twigFunctions);
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
            'rub' => 'RUB',
            123 => 123,
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
            'es' => 'Spain',
            '12' => '12',
        ];

        $sut = $this->getSut($this->localeEn);
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

        $sut = $this->getSut($this->localeEn);
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

        $sut = $this->getSut($this->localeEn);
        $this->assertEquals('02:37 h', $sut->duration($record->getDuration()));
        $this->assertEquals('2.62', $sut->duration($record->getDuration(), true));

        // test Timesheet object
        $this->assertEquals('02:37 h', $sut->duration($record));
        $this->assertEquals('2.62', $sut->duration($record, true));

        // test extended format
        $sut = $this->getSut($this->localeFake, 'XX');
        $this->assertEquals('02 - 37 - 17 Zeit', $sut->duration($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('?', $sut->duration(-1));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('00:00 h', $sut->duration(0));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('00:00 h', $sut->duration(null));
        $this->assertEquals('0', $sut->duration(null, true));
    }

    public function testDurationDecimal()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut($this->localeEn);
        $this->assertEquals('2.62', $sut->durationDecimal($record->getDuration()));

        // test Timesheet object
        $this->assertEquals('2.62', $sut->durationDecimal($record));

        // test extended format
        $sut = $this->getSut($this->localeDe, 'de');
        $this->assertEquals('2,62', $sut->durationDecimal($record->getDuration()));

        // test negative duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0', $sut->durationDecimal(-1));

        // test zero duration
        $sut = $this->getSut($this->localeEn, 'en');
        $this->assertEquals('0', $sut->durationDecimal(0));

        $sut = $this->getSut($this->localeEn, 'en');

        $this->assertEquals('0', $sut->durationDecimal(null));
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
