<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

/**
 * @internal this is subject to change
 */
interface InvoiceFormatter
{
    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedDateTime(\DateTime $date);

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedTime(\DateTime $date);

    /**
     * @param int|float $amount
     * @param string|null $currency
     * @return mixed
     */
    public function getFormattedMoney($amount, $currency);

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(\DateTime $date);

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

    public function getCurrencySymbol(string $currency): string;
}
