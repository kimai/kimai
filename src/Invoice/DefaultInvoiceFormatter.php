<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Twig\DateExtensions;
use App\Twig\LocaleExtensions;

final class DefaultInvoiceFormatter implements InvoiceFormatter
{
    /**
     * @var DateExtensions
     */
    private $dateExtension;
    /**
     * @var LocaleExtensions
     */
    private $extension;

    public function __construct(DateExtensions $dateExtension, LocaleExtensions $extensions)
    {
        $this->dateExtension = $dateExtension;
        $this->extension = $extensions;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedDateTime(\DateTime $date)
    {
        return $this->dateExtension->dateShort($date);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedTime(\DateTime $date)
    {
        return $this->dateExtension->time($date);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(\DateTime $date)
    {
        return $this->dateExtension->monthName($date);
    }

    /**
     * @param float|int $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function getFormattedMoney($amount, ?string $currency, bool $withCurrency = true)
    {
        return $this->extension->money($amount, $currency, $withCurrency);
    }

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDuration($seconds)
    {
        return $this->extension->duration($seconds);
    }

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDecimalDuration($seconds)
    {
        return $this->extension->durationDecimal($seconds);
    }

    public function getCurrencySymbol(string $currency): string
    {
        return $this->extension->currency($currency);
    }
}
