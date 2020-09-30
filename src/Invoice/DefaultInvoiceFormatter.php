<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Configuration\LanguageFormattings;
use App\Utils\LocaleFormatter;

final class DefaultInvoiceFormatter implements InvoiceFormatter
{
    /**
     * @var LocaleFormatter
     */
    private $formatter;

    public function __construct(LanguageFormattings $formats, string $locale)
    {
        $this->formatter = new LocaleFormatter($formats, $locale);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedDateTime(\DateTime $date)
    {
        return $this->formatter->dateShort($date);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedTime(\DateTime $date)
    {
        return $this->formatter->time($date);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(\DateTime $date)
    {
        return $this->formatter->monthName($date);
    }

    /**
     * @param float|int $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function getFormattedMoney($amount, ?string $currency, bool $withCurrency = true)
    {
        return $this->formatter->money($amount, $currency, $withCurrency);
    }

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDuration($seconds)
    {
        return $this->formatter->duration($seconds);
    }

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDecimalDuration($seconds)
    {
        return $this->formatter->durationDecimal($seconds);
    }

    public function getCurrencySymbol(string $currency): string
    {
        return $this->formatter->currency($currency);
    }
}
