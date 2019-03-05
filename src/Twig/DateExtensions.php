<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\LocaleSettings;
use DateTime;
use Twig\TwigFilter;

/**
 * Date specific twig extensions
 */
class DateExtensions extends \Twig_Extension
{
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
    protected $timeFormat = null;
    /**
     * @var bool
     */
    protected $isTwentyFourHour = true;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->dateFormat = $localeSettings->getDateFormat();
        $this->dateTimeFormat = $localeSettings->getDateTimeFormat();
        $this->timeFormat = $localeSettings->getTimeFormat();
        $this->isTwentyFourHour = $localeSettings->isTwentyFourHours();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
            new TwigFilter('date_time', [$this, 'dateTime']),
            new TwigFilter('date_format', [$this, 'dateFormat']),
            new TwigFilter('time', [$this, 'time']),
            new TwigFilter('hour24', [$this, 'hour24']),
        ];
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateShort(DateTime $date)
    {
        return date_format($date, $this->dateFormat);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateTime(DateTime $date)
    {
        return date_format($date, $this->dateTimeFormat);
    }

    /**
     * @param DateTime $date
     * @param string $format
     * @return false|string
     */
    public function dateFormat(DateTime $date, string $format)
    {
        return date_format($date, $format);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function time(DateTime $date)
    {
        return date_format($date, $this->timeFormat);
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function monthName(\DateTime $date)
    {
        return 'month.' . $date->format('n');
    }

    /**
     * @param mixed $twentyFour
     * @param mixed $twelveHour
     * @return mixed
     */
    public function hour24($twentyFour, $twelveHour)
    {
        if ($this->isTwentyFourHour) {
            return $twentyFour;
        }

        return $twelveHour;
    }
}
