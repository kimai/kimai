<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\LanguageFormattings;
use App\Constants;
use App\Utils\LocaleFormats;
use DateTime;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Date specific twig extensions
 */
class DateExtensions extends AbstractExtension
{
    /**
     * @var LocaleFormats|null
     */
    protected $localeFormats = null;
    /**
     * @var string
     */
    protected $dateFormat = null;
    /**
     * @var string
     */
    protected $dateTimeFormat = null;
    /**
     * @var string
     */
    protected $dateTimeTypeFormat = null;
    /**
     * @var string
     */
    protected $timeFormat = null;
    /**
     * @var bool
     */
    protected $isTwentyFourHour = null;
    /**
     * @var string
     */
    private $locale;
    /**
     * @var LanguageFormattings
     */
    private $formats;

    public function __construct(RequestStack $requestStack, LanguageFormattings $formats)
    {
        $locale = Constants::DEFAULT_LOCALE;

        // request is null in a console command
        if (null !== $requestStack->getMasterRequest()) {
            $locale = $requestStack->getMasterRequest()->getLocale();
        }

        $this->formats = $formats;
        $this->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('day_name', [$this, 'dayName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
            new TwigFilter('date_time', [$this, 'dateTime']),
            new TwigFilter('date_full', [$this, 'dateTimeFull']),
            new TwigFilter('date_format', [$this, 'dateFormat']),
            new TwigFilter('time', [$this, 'time']),
            new TwigFilter('hour24', [$this, 'hour24']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('weekend', function ($dateTime) {
                if (!$dateTime instanceof \DateTime) {
                    return false;
                }
                $day = (int) $dateTime->format('w');

                return ($day === 0 || $day === 6);
            }),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_format_duration', [$this, 'getDurationFormat']),
        ];
    }

    /**
     * Allows to switch the locale used for all twig filter and functions.
     *
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
        $this->localeFormats = new LocaleFormats($this->formats, $locale);
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
            } catch (\Exception $ex) {
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
            } catch (\Exception $ex) {
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
            } catch (\Exception $ex) {
                return $date;
            }
        }

        $formatter = new \IntlDateFormatter(
            $this->locale,
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN,
            $this->dateTimeTypeFormat
        );

        return $formatter->format($date);
    }

    /**
     * @param DateTime|string $date
     * @param string $format
     * @return false|string
     * @throws \Exception
     */
    public function dateFormat($date, string $format)
    {
        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (\Exception $ex) {
                return $date;
            }
        }

        return $date->format($format);
    }

    /**
     * @param DateTime|string $date
     * @return string
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
        $formatter = new \IntlDateFormatter(
            $this->locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $dateTime->getTimezone()->getName(),
            \IntlDateFormatter::GREGORIAN,
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

    /**
     * @return string
     */
    public function getDurationFormat()
    {
        return $this->localeFormats->getDurationFormat();
    }
}
