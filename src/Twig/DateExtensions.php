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
use App\Utils\LocaleFormatter;
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
     * @var LocaleFormatter
     */
    private $formatter;
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
        $this->formatter = new LocaleFormatter($this->formats, $locale);
        $this->localeFormats = new LocaleFormats($this->formats, $locale);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateShort($date)
    {
        return $this->formatter->dateShort($date);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateTime($date)
    {
        return $this->formatter->dateTime($date);
    }

    /**
     * @param DateTime|string $date
     * @return bool|false|string
     */
    public function dateTimeFull($date)
    {
        return $this->formatter->dateTimeFull($date);
    }

    /**
     * @param DateTime|string $date
     * @param string $format
     * @return false|string
     * @throws \Exception
     */
    public function dateFormat($date, string $format)
    {
        return $this->formatter->dateFormat($date, $format);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function time($date)
    {
        return $this->formatter->time($date);
    }

    public function monthName(\DateTime $dateTime, bool $withYear = false): string
    {
        return $this->formatter->monthName($dateTime, $withYear);
    }

    public function dayName(\DateTime $dateTime, bool $short = false): string
    {
        return $this->formatter->dayName($dateTime, $short);
    }

    /**
     * @param mixed $twentyFour
     * @param mixed $twelveHour
     * @return mixed
     */
    public function hour24($twentyFour, $twelveHour)
    {
        return $this->formatter->hour24($twentyFour, $twelveHour);
    }

    /**
     * @return string
     */
    public function getDurationFormat()
    {
        return $this->localeFormats->getDurationFormat();
    }
}
