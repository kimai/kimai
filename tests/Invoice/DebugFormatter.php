<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Invoice\InvoiceFormatter;

class DebugFormatter implements InvoiceFormatter
{
    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedDateTime(\DateTime $date)
    {
        return $date->format('d.m.Y');
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedTime(\DateTime $date)
    {
        return $date->format('H:i');
    }

    /**
     * @param mixed $amount
     * @param string|null $currency
     * @return mixed
     */
    public function getFormattedMoney($amount, $currency)
    {
        if (null !== $currency) {
            return $amount . ' ' . $currency;
        }

        return $amount;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(\DateTime $date)
    {
        return $date->format('m');
    }

    /**
     * @param mixed $seconds
     * @return mixed
     */
    public function getFormattedDuration($seconds)
    {
        return $seconds;
    }

    /**
     * @param mixed $seconds
     * @return mixed
     */
    public function getFormattedDecimalDuration($seconds)
    {
        return $seconds;
    }

    public function getCurrencySymbol(string $currency): string
    {
        return $currency;
    }
}
