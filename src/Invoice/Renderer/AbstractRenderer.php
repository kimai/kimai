<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Twig\DateExtensions;
use App\Twig\Extensions;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractRenderer
{
    use RendererTrait;

    /**
     * @var DateExtensions
     */
    protected $dateExtension;

    /**
     * @var Extensions
     */
    protected $extension;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
    protected function getFormattedDateTime(\DateTime $date)
    {
        return $this->dateExtension->dateShort($date);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedTime(\DateTime $date)
    {
        return $this->dateExtension->time($date);
    }

    /**
     * @param $amount
     * @param $currency
     * @return string
     */
    protected function getFormattedMoney($amount, $currency)
    {
        return $this->extension->money($amount, $currency);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedMonthName(\DateTime $date)
    {
        return $this->translator->trans($this->dateExtension->monthName($date));
    }

    /**
     * @param $seconds
     * @return mixed
     */
    protected function getFormattedDuration($seconds)
    {
        return $this->extension->duration($seconds);
    }
}
