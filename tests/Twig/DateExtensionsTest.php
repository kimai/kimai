<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\DateExtensions;
use App\Utils\LocaleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;

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

        $localeSettings = new LocaleSettings($requestStack, $dateSettings);

        return new DateExtensions($localeSettings);
    }

    public function testGetFilters()
    {
        $filters = ['month_name', 'date_short'];
        $sut = $this->getSut('de', []);
        $twigFilters = $sut->getFilters();
        $this->assertCount(count($filters), $twigFilters);
        $i = 0;
        foreach ($twigFilters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertEquals($filters[$i++], $filter->getName());
        }
    }

    /**
     * @param string $locale
     * @param \DateTime $date
     * @param string $result
     * @dataProvider getDateShortData
     */
    public function testDateShort($locale, \DateTime $date, $result)
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
}
