<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\LocaleService;
use App\Constants;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Utils\JavascriptFormatConverter;
use App\Utils\LocaleFormatter;
use DateTime;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class LocaleFormatExtensions extends AbstractExtension implements LocaleAwareInterface
{
    private ?bool $fdowSunday = null;
    private ?LocaleFormatter $formatter = null;
    private ?string $locale = null;

    public function __construct(private LocaleService $localeService, private Security $security)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('day_name', [$this, 'dayName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
            new TwigFilter('date_time', [$this, 'dateTime']),
            new TwigFilter('date_full', [$this, 'dateTimeFull']), // deprecated: needs to be kept for invoice and export templates
            new TwigFilter('date_format', [$this, 'dateFormat']),
            new TwigFilter('date_weekday', [$this, 'dateWeekday']),
            new TwigFilter('time', [$this, 'time']),
            new TwigFilter('duration', [$this, 'duration']),
            new TwigFilter('chart_duration', [$this, 'durationChart']),
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('amount', [$this, 'amount']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('weekend', [$this, 'isWeekend']),
            new TwigTest('today', function ($dateTime) {
                if (!$dateTime instanceof \DateTime) {
                    return false;
                }
                $compare = new \DateTime('now', $dateTime->getTimezone());

                return $compare->format('Y-m-d') === $dateTime->format('Y-m-d');
            }),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('javascript_configurations', [$this, 'getJavascriptConfiguration']),
            new TwigFunction('create_date', [$this, 'createDate']),
            new TwigFunction('month_names', [$this, 'getMonthNames']),
            new TwigFunction('javascript_format', [$this, 'getJavascriptFormat']),
        ];
    }

    /**
     * Allows to switch the locale used for all twig filter and functions.
     *
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->formatter = null;
    }

    private function getFormatter(): LocaleFormatter
    {
        if (null === $this->formatter) {
            $this->formatter = new LocaleFormatter($this->localeService, $this->getLocale());
        }

        return $this->formatter;
    }

    public function getLocale(): string
    {
        if (null === $this->locale) {
            $this->locale = \Locale::getDefault();
        }

        return $this->locale;
    }

    /**
     * @param DateTime $dateTime
     * @return bool
     */
    public function isWeekend($dateTime): bool
    {
        if (!$dateTime instanceof \DateTime) {
            return false;
        }

        $day = (int) $dateTime->format('w');

        if ($this->fdowSunday === null) {
            /** @var User|null $user */
            $user = $this->security->getUser();
            if ($user !== null) {
                $this->fdowSunday = $user->isFirstDayOfWeekSunday();
            } else {
                $this->fdowSunday = false;
            }
        }

        if ($this->fdowSunday) {
            return ($day === 5 || $day === 6);
        }

        return ($day === 0 || $day === 6);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateShort($date)
    {
        return $this->getFormatter()->dateShort($date);
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function dateTime($date)
    {
        return $this->getFormatter()->dateTime($date);
    }

    /**
     * @param DateTime|string $date
     * @return bool|false|string
     */
    public function dateTimeFull($date)
    {
        @trigger_error('Twig filter "date_full" is deprecated and will be removed soon. Use "date_time" instead', E_USER_DEPRECATED);

        return $this->getFormatter()->dateTime($date);
    }

    public function createDate(string $date, ?User $user = null): \DateTime
    {
        $timezone = $user !== null ? $user->getTimezone() : date_default_timezone_get();

        return new DateTime($date, new \DateTimeZone($timezone));
    }

    /**
     * @param DateTime|string $date
     * @param string $format
     * @return false|string
     * @throws \Exception
     */
    public function dateFormat($date, string $format)
    {
        return $this->getFormatter()->dateFormat($date, $format);
    }

    public function dateWeekday(DateTime $date): string
    {
        return $this->dayName($date, true) . ' ' . $this->getFormatter()->dateFormat($date, 'd');
    }

    /**
     * @param DateTime|string $date
     * @return string
     */
    public function time($date)
    {
        return $this->getFormatter()->time($date);
    }

    /**
     * @param string|null $year
     * @return string[]
     */
    public function getMonthNames(?string $year = null): array
    {
        $withYear = true;
        if ($year === null) {
            $year = date('Y');
            $withYear = false;
        }
        $months = [];
        for ($i = 1; $i < 13; $i++) {
            $months[] = $this->getFormatter()->monthName(new DateTime(sprintf('%s-%s-10', $year, ($i < 10 ? '0' . $i : (string) $i))), $withYear);
        }

        return $months;
    }

    public function monthName(\DateTime $dateTime, bool $withYear = false): string
    {
        return $this->getFormatter()->monthName($dateTime, $withYear);
    }

    public function dayName(\DateTime $dateTime, bool $short = false): string
    {
        return $this->getFormatter()->dayName($dateTime, $short);
    }

    public function getJavascriptConfiguration(User $user): array
    {
        $converter = new JavascriptFormatConverter();

        return [
            'formatDuration' => $this->localeService->getDurationFormat($this->locale),
            'formatDate' => $converter->convert($this->localeService->getDateFormat($this->locale)),
            'defaultColor' => Constants::DEFAULT_COLOR,
            'twentyFourHours' => $this->localeService->is24Hour($this->locale),
            'updateBrowserTitle' => (bool) $user->getPreferenceValue('update_browser_title'),
        ];
    }

    public function getJavascriptFormat(string $name): string
    {
        $converter = new JavascriptFormatConverter();
        $time = $converter->convert($this->localeService->getTimeFormat($this->locale));
        $date = $converter->convert($this->localeService->getDateFormat($this->locale));

        switch ($name) {
            case 'date':
                return $date;

            case 'time':
                return $time;

            case 'datetime':
            case 'date-time':
                return $date . ' ' . $time;
        }

        throw new \InvalidArgumentException('Unknown format name: ' . $name);
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
        return $this->getFormatter()->duration($duration, $decimal);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet|null $duration
     * @return string
     */
    public function durationDecimal($duration)
    {
        return $this->getFormatter()->durationDecimal($duration);
    }

    public function durationChart($duration): string
    {
        return number_format(($duration / 3600), 2, '.', '');
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->getFormatter()->amount($amount);
    }

    /**
     * @param float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function money($amount, ?string $currency = null, bool $withCurrency = true)
    {
        return $this->getFormatter()->money($amount, $currency, $withCurrency);
    }
}
