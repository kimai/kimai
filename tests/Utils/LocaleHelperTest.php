<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Entity\Timesheet;
use App\Utils\LocaleHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @covers \App\Utils\LocaleHelper
 */
class LocaleHelperTest extends TestCase
{
    protected function getSut(string $locale): LocaleHelper
    {
        return new LocaleHelper($locale);
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
            'es' => 'Spain',
            '12' => '12',
        ];

        $sut = $this->getSut('en');
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

        $sut = $this->getSut('en');
        foreach ($languages as $locale => $name) {
            $this->assertEquals($name, $sut->language($locale));
        }
    }

    public function testMoneyWithoutCurrency()
    {
        $sut = $this->getSut('en');
        $this->assertEquals('123.75', $sut->money(123.75));

        $sut = $this->getSut('de');
        $this->assertEquals('123.234,76', $sut->money(123234.7554, null, true));
        $this->assertEquals('123.234,76', $sut->money(123234.7554, null, false));
        $this->assertEquals('123.234,76', $sut->money(123234.7554, 'EUR', false));
    }

    /**
     * @dataProvider getMoneyNoCurrencyData
     */
    public function testMoneyNoCurrency($result, $amount, $currency, $locale)
    {
        $sut = $this->getSut($locale);
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
        $sut = $this->getSut($locale);
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
        $sut = $this->getSut($locale);
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

        $sut = $this->getSut($locale);
        $this->assertEquals($result, $sut->money($amount, $currency));
    }

    public function getMoneyData62_1()
    {
        return [
            ['RUB 13.50', 13.50, 'RUB', 'en'],
            ['13,75 ₽', 13.75, 'RUB', 'ru'],
        ];
    }

    public function testDurationDecimal()
    {
        $record = $this->getTimesheet(9437);

        $sut = $this->getSut('en');
        $this->assertEquals('2.62', $sut->durationDecimal($record->getDuration()));

        // test extended format
        $sut = $this->getSut('de');
        $this->assertEquals('2,62', $sut->durationDecimal($record->getDuration()));

        // test negative duration
        $sut = $this->getSut('en');
        $this->assertEquals('0', $sut->durationDecimal('-1'));

        // test zero duration
        $sut = $this->getSut('en');
        $this->assertEquals('0', $sut->durationDecimal('0'));
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
