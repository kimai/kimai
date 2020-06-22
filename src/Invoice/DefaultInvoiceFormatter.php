<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Twig\DateExtensions;
use App\Twig\Extensions;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DefaultInvoiceFormatter implements InvoiceFormatter
{
    /**
     * @var DateExtensions
     */
    private $dateExtension;

    /**
     * @var Extensions
     */
    private $extension;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     * @param DateExtensions $dateExtension
     * @param Extensions $extensions
     */
    public function __construct(TranslatorInterface $translator, DateExtensions $dateExtension, Extensions $extensions)
    {
        $this->translator = $translator;
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
     * @param int $amount
     * @param string $currency
     * @return string
     */
    public function getFormattedMoney($amount, $currency)
    {
        return $this->extension->money($amount, $currency);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function getFormattedMonthName(\DateTime $date)
    {
        return $this->translator->trans($this->dateExtension->monthName($date));
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
