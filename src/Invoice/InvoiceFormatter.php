<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use DateTime;

/**
 * @internal this is subject to change
 */
interface InvoiceFormatter
{
    /**
     * @param DateTime $date
     * @return mixed
     */
    public function getFormattedDateTime(DateTime $date);

    /**
     * @param DateTime $date
     * @return mixed
     */
    public function getFormattedTime(DateTime $date);

    /**
     * @param int|float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function getFormattedMoney($amount, ?string $currency, bool $withCurrency = true);

    /**
     * @param DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(DateTime $date);

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDuration($seconds);

    /**
     * @param int $seconds
     * @return mixed
     */
    public function getFormattedDecimalDuration($seconds);

    /**
     * Returns the currency symbol for the given currency by name.
     *
     * @param string $currency
     * @return string
     */
    public function getCurrencySymbol(string $currency): string;
}
