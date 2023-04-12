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
    public function getFormattedDateTime(\DateTime $date): string
    {
        return $date->format('d.m.Y');
    }

    public function getFormattedTime(\DateTime $date): string
    {
        return $date->format('H:i');
    }

    public function getFormattedMoney(float $amount, ?string $currency, bool $withCurrency = true): string
    {
        if (null === $currency) {
            $withCurrency = false;
        }

        if ($withCurrency) {
            return $amount . ' ' . $currency;
        }

        return (string) $amount;
    }

    public function getFormattedMonthName(\DateTime $date): string
    {
        return $date->format('m');
    }

    public function getFormattedDuration(int $seconds): string
    {
        return (string) $seconds;
    }

    public function getFormattedDecimalDuration(int $seconds): string
    {
        return (string) $seconds;
    }

    public function getCurrencySymbol(string $currency): string
    {
        return $currency;
    }

    public function getLocale(): string
    {
        return 'en';
    }

    public function setLocale(string $locale): void
    {
        // does nothing
    }

    public function getFormattedAmount(float $amount): string
    {
        return (string) $amount;
    }
}
