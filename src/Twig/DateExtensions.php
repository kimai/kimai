<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use DateTime;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;

/**
 * Date specific twig extensions
 */
class DateExtensions extends \Twig_Extension
{
    private const FALLBACK_SHORT = 'Y-m-d';

    /**
     * @var array
     */
    protected $dateSettings;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param array $languageSettings
     */
    public function __construct(RequestStack $requestStack, array $languageSettings)
    {
        $this->requestStack = $requestStack;
        $this->dateSettings = $languageSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
        ];
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->requestStack->getCurrentRequest()->getLocale();
    }

    /**
     * @param array $context
     * @param DateTime $date
     * @return string
     */
    public function dateShort(DateTime $date)
    {
        $locale = $this->getLocale();
        $format = self::FALLBACK_SHORT;

        if (isset($this->dateSettings[$locale]['date_short'])) {
            $format = $this->dateSettings[$locale]['date_short'];
        }

        return date_format($date, $format);
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function monthName(\DateTime $date)
    {
        return 'month.' . $date->format('n');
    }
}
