<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use NumberFormatter;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Languages;

final class LocaleHelper
{
    /**
     * @var string
     */
    private $locale;
    /**
     * @var NumberFormatter
     */
    private $numberFormatter;
    /**
     * @var NumberFormatter
     */
    private $moneyFormatter;
    /**
     * @var NumberFormatter
     */
    private $moneyFormatterNoCurrency;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int $seconds
     * @return string
     */
    public function durationDecimal(int $seconds)
    {
        return $this->getNumberFormatter()->format(number_format($seconds / 3600, 2));
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->getNumberFormatter()->format($amount);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        try {
            return Currencies::getSymbol(strtoupper($currency), $this->locale);
        } catch (\Exception $ex) {
        }

        return $currency;
    }

    /**
     * @param string $language
     * @return string
     */
    public function language(string $language)
    {
        try {
            return Languages::getName(strtolower($language), $this->locale);
        } catch (\Exception $ex) {
        }

        return $language;
    }

    /**
     * @param string $country
     * @return string
     */
    public function country(string $country)
    {
        try {
            return Countries::getName(strtoupper($country), $this->locale);
        } catch (\Exception $ex) {
        }

        return $country;
    }

    /**
     * @param int|float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function money($amount, ?string $currency = null, bool $withCurrency = true)
    {
        if (null === $currency) {
            $withCurrency = false;
        }

        if (false === $withCurrency) {
            return $this->getMoneyFormatter($withCurrency)->format($amount, NumberFormatter::TYPE_DEFAULT);
        }

        return $this->getMoneyFormatter($withCurrency)->formatCurrency($amount, $currency);
    }

    private function getNumberFormatter(): NumberFormatter
    {
        if (null === $this->numberFormatter) {
            $this->numberFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        }

        return $this->numberFormatter;
    }

    private function getMoneyFormatter(bool $withCurrency = true): NumberFormatter
    {
        if ($withCurrency) {
            if (null === $this->moneyFormatter) {
                $this->moneyFormatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
            }

            return $this->moneyFormatter;
        }

        if (null === $this->moneyFormatterNoCurrency) {
            $this->moneyFormatterNoCurrency = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
            $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_PREFIX, '');
            $this->moneyFormatterNoCurrency->setTextAttribute(NumberFormatter::POSITIVE_SUFFIX, '');
        }

        return $this->moneyFormatterNoCurrency;
    }
}
