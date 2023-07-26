<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use DateTimeInterface;

/**
 * @internal this is subject to change
 */
interface InvoiceFormatter
{
    public function getLocale(): string;

    public function setLocale(string $locale): void;

    public function getFormattedDateTime(DateTimeInterface $date): string;

    public function getFormattedTime(DateTimeInterface $date): string;

    public function getFormattedAmount(float $amount): string;

    public function getFormattedMoney(float $amount, ?string $currency, bool $withCurrency = true): string;

    public function getFormattedMonthName(DateTimeInterface $date): string;

    public function getFormattedDuration(int $seconds): string;

    public function getFormattedDecimalDuration(int $seconds): string;

    public function getCurrencySymbol(string $currency): string;
}
