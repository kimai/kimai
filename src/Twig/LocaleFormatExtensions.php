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
use App\Utils\FormFormatConverter;
use App\Utils\JavascriptFormatConverter;
use App\Utils\LocaleFormatter;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
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
            new TwigFilter('date_full', [$this, 'dateTime']), // deprecated: needs to be kept for invoice and export templates
            new TwigFilter('date_format', [$this, 'dateFormat']),
            new TwigFilter('date_weekday', [$this, 'dateWeekday']),
            new TwigFilter('time', [$this, 'time']),
            new TwigFilter('duration', [$this, 'duration']),
            new TwigFilter('chart_duration', [$this, 'durationChart']),
            new TwigFilter('chart_money', [$this, 'moneyChart']),
            new TwigFilter('duration_decimal', [$this, 'durationDecimal']),
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('amount', [$this, 'amount']),
            new TwigFilter('js_format', [$this, 'convertJavascriptFormat']),
            new TwigFilter('pattern', [$this, 'convertHtmlPattern']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('weekend', [$this, 'isWeekend']),
            new TwigTest('today', function ($dateTime): bool {
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
            new TwigFunction('locale_format', [$this, 'getLocaleFormat']),
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

    public function isWeekend(\DateTimeInterface|string|null $dateTime): bool
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

    public function dateShort(\DateTimeInterface|string|null $date): string
    {
        return (string) $this->getFormatter()->dateShort($date);
    }

    public function dateTime(DateTime|string|null $date): string
    {
        return (string) $this->getFormatter()->dateTime($date);
    }

    public function createDate(string $date, ?User $user = null): \DateTime
    {
        $timezone = $user !== null ? $user->getTimezone() : date_default_timezone_get();

        return new DateTime($date, new \DateTimeZone($timezone));
    }

    public function dateFormat(\DateTimeInterface|string|null $date, string $format): string
    {
        return (string) $this->getFormatter()->dateFormat($date, $format);
    }

    public function dateWeekday(\DateTimeInterface $date): string
    {
        return $this->dayName($date, true) . ' ' . $this->getFormatter()->dateFormat($date, 'd');
    }

    public function time(\DateTimeInterface|string|null $date): string
    {
        return (string) $this->getFormatter()->time($date);
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

    public function monthName(\DateTimeInterface $dateTime, bool $withYear = false): string
    {
        return $this->getFormatter()->monthName($dateTime, $withYear);
    }

    public function dayName(\DateTimeInterface $dateTime, bool $short = false): string
    {
        return $this->getFormatter()->dayName($dateTime, $short);
    }

    public function getJavascriptConfiguration(User $user): array
    {
        return [
            'formatDuration' => $this->localeService->getDurationFormat($this->locale),
            'formatDate' => $this->localeService->getDateFormat($this->locale),
            'defaultColor' => Constants::DEFAULT_COLOR,
            'twentyFourHours' => $this->localeService->is24Hour($this->locale),
            'updateBrowserTitle' => (bool) $user->getPreferenceValue('update_browser_title'),
            'timezone' => $user->getTimezone(),
        ];
    }

    public function getLocaleFormat(string $name): string
    {
        $timeFormat = $this->localeService->getTimeFormat($this->locale);
        $dateFormat = $this->localeService->getDateFormat($this->locale);

        return match ($name) {
            'date' => $dateFormat,
            'time' => $timeFormat,
            'datetime', 'date-time' => $dateFormat . ' ' . $timeFormat,
            default => throw new \InvalidArgumentException('Unknown format name: ' . $name),
        };
    }

    public function convertJavascriptFormat(string $format): string
    {
        $converter = new JavascriptFormatConverter();

        return $converter->convert($format);
    }

    public function convertHtmlPattern(string $format): string
    {
        $converter = new FormFormatConverter();

        return $converter->convertToPattern($format);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet|null $duration
     * @param bool $decimal
     * @return string
     */
    public function duration(Timesheet|int|string|null $duration, bool $decimal = false): string
    {
        return $this->getFormatter()->duration($duration, $decimal);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     */
    public function durationDecimal(Timesheet|int|string|null $duration): string
    {
        return $this->getFormatter()->durationDecimal($duration);
    }

    /**
     * Transforms seconds into a decimal formatted duration string, for usage with the chart library.
     */
    public function durationChart(int|null $duration): string
    {
        if ($duration === null) {
            $duration = 0;
        }

        return number_format(\floatval($duration / 3600), 2, '.', '');
    }

    public function moneyChart(int|float|string|null $money): string
    {
        if ($money === null) {
            $money = 0;
        }

        return number_format(\floatval($money), 2, '.', '');
    }

    public function amount(null|int|float|string $amount): string
    {
        return $this->getFormatter()->amount($amount);
    }

    public function money(float|int|null $amount, ?string $currency = null, bool $withCurrency = true): string
    {
        return $this->getFormatter()->money($amount, $currency, $withCurrency);
    }
}
