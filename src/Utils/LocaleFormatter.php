<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Configuration\LanguageFormattings;
use App\Entity\Timesheet;
use DateTime;
use Exception;
use IntlDateFormatter;
use Symfony\Component\Intl\Locales;

/**
 * Use this class to format values into locale specific representations.
 */
final class LocaleFormatter
{
    /**
     * @var LocaleFormats
     */
    private $localeFormats;
    /**
     * @var Duration
     */
    private $durationFormatter;
    /**
     * @var LocaleHelper
     */
    private $helper;
    /**
     * @var string
     */
    private $locale;
    // ---------------- private cache below ----------------
    /**
     * @var string
     */
    private $dateFormat = null;
    /**
     * @var string
     */
    private $dateTimeFormat = null;
    /**
     * @var string
     */
    private $dateTimeTypeFormat = null;
    /**
     * @var string
     */
    private $timeFormat = null;
    /**
     * @var bool
     */
    private $isTwentyFourHour = null;

    public function __construct(LanguageFormattings $formats, string $locale)
    {
        $this->locale = $locale;
        $this->durationFormatter = new Duration();
        $this->helper = new LocaleHelper($locale);
        $this->localeFormats = new LocaleFormats($formats, $locale);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet|null $duration
     * @param bool $decimal
     * @return string
     */
    public function duration($duration, $decimal = false)
    {
        if ($decimal) {
            return $this->durationDecimal($duration);
        }

        $seconds = $this->getSecondsForDuration($duration);
        $format = $this->localeFormats->getDurationFormat();

        return $this->formatDuration($seconds, $format);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet|null $duration
     * @return string
     */
    public function durationDecimal($duration)
    {
        $seconds = $this->getSecondsForDuration($duration);

        return $this->helper->durationDecimal($seconds);
    }

    /**
     * @param int|Timesheet|null $duration
     * @return int
     */
    private function getSecondsForDuration($duration): int
    {
        if (null === $duration) {
            return 0;
        }

        if ($duration instanceof Timesheet) {
            if (null === $duration->getEnd()) {
                $duration = time() - $duration->getBegin()->getTimestamp();
            } else {
                $duration = $duration->getDuration();
            }
        }

        return (int) $duration;
    }

    private function formatDuration(int $seconds, string $format): string
    {
        if ($seconds < 0) {
            return '?';
        }

        return $this->durationFormatter->format($seconds, $format);
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->helper->amount($amount);
    }

    /**
     * Returns the currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        return $this->helper->currency($currency);
    }

    /**
     * @param string $language
     * @return string
     */
    public function language($language)
    {
        return $this->helper->language($language);
    }

    /**
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        return $this->helper->country($country);
    }

    /**
     * @param float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function money($amount, ?string $currency = null, bool $withCurrency = true)
    {
        return $this->helper->money($amount, $currency, $withCurrency);
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.)
     *
     * @return array
     */
    public function getLocales()
    {
        $locales = [];
        foreach ($this->localeFormats->getAvailableLanguages() as $locale) {
            $locales[] = ['code' => $locale, 'name' => Locales::getName($locale, $locale)];
        }

        return $locales;
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateShort($date)
    {
        if (null === $this->dateFormat) {
            $this->dateFormat = $this->localeFormats->getDateFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $date->format($this->dateFormat);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateTime($date)
    {
        if (null === $this->dateTimeFormat) {
            $this->dateTimeFormat = $this->localeFormats->getDateTimeFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $date->format($this->dateTimeFormat);
    }

    /**
     * @param DateTime|string $date
     * @return bool|false|string
     */
    public function dateTimeFull($date)
    {
        if (null === $this->dateTimeTypeFormat) {
            $this->dateTimeTypeFormat = $this->localeFormats->getDateTimeTypeFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::MEDIUM,
            date_default_timezone_get(),
            IntlDateFormatter::GREGORIAN,
            $this->dateTimeTypeFormat
        );

        return $formatter->format($date);
    }

    /**
     * @param DateTime|string $date
     * @param string $format
     * @return false|string
     * @throws Exception
     */
    public function dateFormat($date, string $format)
    {
        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (Exception $ex) {
                return $date;
            }
        }

        return $date->format($format);
    }

    /**
     * @param DateTime|string $date
     * @return string
     * @throws Exception
     */
    public function time($date)
    {
        if (null === $this->timeFormat) {
            $this->timeFormat = $this->localeFormats->getTimeFormat();
        }

        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }

        return $date->format($this->timeFormat);
    }

    /**
     * @see https://framework.zend.com/manual/1.12/en/zend.date.constants.html#zend.date.constants.selfdefinedformats
     * @see http://userguide.icu-project.org/formatparse/datetime
     *
     * @param DateTime $dateTime
     * @param string $format
     * @return string
     */
    private function formatIntl(\DateTime $dateTime, string $format): string
    {
        $formatter = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $dateTime->getTimezone()->getName(),
            IntlDateFormatter::GREGORIAN,
            $format
        );

        return $formatter->format($dateTime);
    }

    public function monthName(\DateTime $dateTime, bool $withYear = false): string
    {
        return $this->formatIntl($dateTime, ($withYear ? 'LLLL yyyy' : 'LLLL'));
    }

    public function dayName(\DateTime $dateTime, bool $short = false): string
    {
        return $this->formatIntl($dateTime, ($short ? 'EE' : 'EEEE'));
    }

    /**
     * @param mixed $twentyFour
     * @param mixed $twelveHour
     * @return mixed
     */
    public function hour24($twentyFour, $twelveHour)
    {
        if (null === $this->isTwentyFourHour) {
            $this->isTwentyFourHour = $this->localeFormats->isTwentyFourHours();
        }

        if (true === $this->isTwentyFourHour) {
            return $twentyFour;
        }

        return $twelveHour;
    }
}
