<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Configuration\LocaleService;
use App\Entity\Timesheet;
use DateTime;
use Exception;
use IntlDateFormatter;
use NumberFormatter;
use Symfony\Component\Intl\Currencies;

/**
 * Use this class to format values into locale specific representations.
 */
final class LocaleFormatter
{
    private ?Duration $durationFormatter = null;
    private ?IntlDateFormatter $dateFormatter = null;
    private ?IntlDateFormatter $dateTimeFormatter = null;
    private ?IntlDateFormatter $timeFormatter = null;
    private ?NumberFormatter $numberFormatter = null;
    private ?NumberFormatter $decimalFormatter = null;
    private ?NumberFormatter $moneyFormatter = null;
    private ?NumberFormatter $moneyFormatterNoCurrency = null;

    public function __construct(private LocaleService $localeService, private string $locale)
    {
    }

    /**
     * Transforms seconds into a duration string.
     */
    public function duration(int|Timesheet|string|null $duration, bool $decimal = false): string
    {
        if ($decimal) {
            return $this->durationDecimal($duration);
        }

        return $this->formatDuration(
            $this->getSecondsForDuration($duration),
            $this->localeService->getDurationFormat($this->locale)
        );
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     */
    public function durationDecimal(Timesheet|int|string|null $duration): string
    {
        if (null === $this->numberFormatter) {
            $this->decimalFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $this->decimalFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        }

        $seconds = $this->getSecondsForDuration($duration);

        $value = round($seconds / 3600, 2);

        return $this->decimalFormatter->format($value);
    }

    private function getSecondsForDuration(string|int|Timesheet|null $duration): int
    {
        if ($duration === null || $duration === '') {
            return 0;
        }

        if ($duration instanceof Timesheet) {
            if (null === $duration->getEnd()) {
                $duration = time() - $duration->getBegin()->getTimestamp();
            } else {
                $duration = $duration->getDuration() ?? 0;
            }
        }

        return (int) $duration;
    }

    private function formatDuration(int $seconds, string $format): string
    {
        if ($this->durationFormatter === null) {
            $this->durationFormatter = new Duration();
        }

        return $this->durationFormatter->format($seconds, $format);
    }

    /**
     * Used in twig filter |amount and invoice templates.
     */
    public function amount(null|int|float|string $amount): string
    {
        if ($amount === null || $amount === '') {
            return '0';
        }

        if (null === $this->numberFormatter) {
            $this->numberFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        }

        $formatted = $this->numberFormatter->format($amount);

        if (!\is_string($formatted)) {
            throw new \Exception('Could not convert into monetary string: ' . $amount);
        }

        return $formatted;
    }

    /**
     * Returns the currency symbol.
     */
    public function currency(?string $currency): string
    {
        if ($currency === null) {
            return '';
        }

        try {
            return Currencies::getSymbol(strtoupper($currency), $this->locale);
        } catch (\Exception $ex) {
        }

        return $currency;
    }

    public function money(null|int|float $amount, ?string $currency = null, bool $withCurrency = true): string
    {
        if ($currency === null) {
            $withCurrency = false;
        }

        if ($amount === null) {
            $amount = 0;
        }

        if (false === $withCurrency) {
            if (null === $this->moneyFormatterNoCurrency) {
                $this->moneyFormatterNoCurrency = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_PREFIX, '');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_SUFFIX, '');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::NEGATIVE_PREFIX, '-');
                $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::NEGATIVE_SUFFIX, '');
            }

            return $this->moneyFormatterNoCurrency->format($amount, NumberFormatter::TYPE_DEFAULT);
        }

        if (null === $this->moneyFormatter) {
            $this->moneyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
        }

        return $this->moneyFormatter->formatCurrency($amount, $currency);
    }

    public function dateShort(\DateTimeInterface|string|null $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (null === $this->dateFormatter) {
            $this->dateFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getDateFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return null;
            }
        }

        $formatted = $this->dateFormatter->format($date);

        if ($formatted === false) {
            return null;
        }

        return (string) $formatted;
    }

    public function dateTime(DateTime|string|null $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (null === $this->dateTimeFormatter) {
            $this->dateTimeFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getDateTimeFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return null;
            }
        }

        $formatted = $this->dateTimeFormatter->format($date);

        if ($formatted === false) {
            return null;
        }

        return (string) $formatted;
    }

    public function dateFormat(\DateTimeInterface|string|null $date, string $format): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return null;
            }
        }

        return $date->format($format);
    }

    public function time(\DateTimeInterface|string|null $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (null === $this->timeFormatter) {
            $this->timeFormatter = new IntlDateFormatter(
                $this->locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::MEDIUM,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $this->localeService->getTimeFormat($this->locale)
            );
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $date = new \DateTimeImmutable($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        $formatted = $this->timeFormatter->format($date);

        if ($formatted === false) {
            return null;
        }

        return (string) $formatted;
    }

    /**
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/
     */
    private function formatIntl(\DateTimeInterface $dateTime, string $format): string
    {
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $dateTime->getTimezone()->getName(),
            IntlDateFormatter::GREGORIAN,
            $format
        );

        $formatted = $formatter->format($dateTime);

        if ($formatted === false) {
            throw new \Exception('Invalid dateformat given for formatIntl()');
        }

        return (string) $formatted;
    }

    public function monthName(\DateTimeInterface $dateTime, bool $withYear = false): string
    {
        return $this->formatIntl($dateTime, ($withYear ? 'LLLL yyyy' : 'LLLL'));
    }

    public function dayName(\DateTimeInterface $dateTime, bool $short = false): string
    {
        return $this->formatIntl($dateTime, ($short ? 'EE' : 'EEEE'));
    }
}
