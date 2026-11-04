<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Configuration\LocaleService;
use App\Entity\Timesheet;
use App\Utils\LocaleFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocaleFormatter::class)]
class LocaleFormatterTest extends TestCase
{
    private ?string $oldTimezone = null;

    protected function setUp(): void
    {
        $this->oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Vienna');
    }

    protected function tearDown(): void
    {
        if ($this->oldTimezone !== null) {
            date_default_timezone_set($this->oldTimezone);
        }

        $this->oldTimezone = null;
    }

    public function testDurationFormattingWithTimesheet(): void
    {
        $sut = $this->getSut('en');

        $timesheet = (new Timesheet())
            ->setBegin(new \DateTime('2020-07-09 08:00:00', new \DateTimeZone('Europe/Vienna')))
            ->setEnd(new \DateTime('2020-07-09 10:37:17', new \DateTimeZone('Europe/Vienna')))
            ->setDuration(9437);

        self::assertSame('2:37', $sut->duration($timesheet));
        self::assertSame('2.62', $sut->duration($timesheet, true));
        self::assertSame('2.62', $sut->durationDecimal($timesheet));
    }

    public function testDurationDecimalWorksAfterAmountFormatting(): void
    {
        $sut = $this->getSut('de');

        self::assertSame('1.234,5', $sut->amount(1234.5));
        self::assertSame('2,62', $sut->durationDecimal(9437));
    }

    public function testAmountAndMoneyFormatting(): void
    {
        $sut = $this->getSut('en');

        self::assertSame('0', $sut->amount(null));
        self::assertSame('2,345.009', $sut->amount(2345.009));
        self::assertSame('€2,345.01', $sut->money(2345.009, 'EUR'));
        self::assertSame('2,345.01', $sut->money(2345.009, 'EUR', false));
    }

    public function testCurrencyFormattingFallsBackToInput(): void
    {
        $sut = $this->getSut('de');

        self::assertSame('', $sut->currency(null));
        self::assertSame('€', $sut->currency('eur'));
        self::assertSame('INVALID', $sut->currency('INVALID'));
    }

    public function testDateAndTimeFormatting(): void
    {
        $sut = $this->getSut('de');
        $date = new \DateTimeImmutable('1980-12-14 13:27:55', new \DateTimeZone('Europe/Vienna'));

        self::assertSame('14.12.1980', $sut->dateShort($date));
        self::assertSame('14.12.1980 13:27:55', $sut->dateTime($date));
        self::assertSame('1980-12-14T13:27:55+01:00', $sut->dateFormat($date, 'c'));
        self::assertSame('13:27:55', $sut->time($date));
    }

    public function testInvalidDateInputReturnsNull(): void
    {
        $sut = $this->getSut('en');

        self::assertNull($sut->dateShort('not-a-date'));
        self::assertNull($sut->dateTime('not-a-date'));
        self::assertNull($sut->dateFormat('not-a-date', 'c'));
        self::assertNull($sut->time('not-a-date'));
    }

    public function testLocalizedNames(): void
    {
        $sut = $this->getSut('en');
        $date = new \DateTimeImmutable('2020-07-09 12:00:00', new \DateTimeZone('Europe/Vienna'));

        self::assertSame('July', $sut->monthName($date));
        self::assertSame('July 2020', $sut->monthName($date, true));
        self::assertSame('Q3', $sut->quarterName($date));
        self::assertSame('Q3 2020', $sut->quarterName($date, true));
        self::assertSame('Thursday', $sut->dayName($date));
        self::assertSame('Thu', $sut->dayName($date, true));
    }

    private function getSut(string $locale): LocaleFormatter
    {
        return new LocaleFormatter(new LocaleService([
            'de' => array_merge(LocaleService::DEFAULT_SETTINGS, ['date' => 'dd.MM.Y', 'time' => 'HH:mm:ss']),
            'en' => array_merge(LocaleService::DEFAULT_SETTINGS, ['date' => 'Y-MM-dd', 'time' => 'HH:mm']),
        ]), $locale);
    }
}
