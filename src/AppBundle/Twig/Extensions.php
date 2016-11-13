<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Twig;

use AppBundle\Utils\Markdown;
use Symfony\Component\Intl\Intl;
use DateTime;
use DateInterval;

/**
 * Multiple Twig extensions: filters and functions
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Extensions extends \Twig_Extension
{
    /**
     * @var Markdown
     */
    private $parser;

    /**
     * @var array
     */
    private $locales;

    /**
     * Extensions constructor.
     * @param Markdown $parser
     * @param $locales
     */
    public function __construct(Markdown $parser, $locales)
    {
        $this->parser = $parser;
        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('md2html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('duration', array($this, 'duration')),
            new \Twig_SimpleFilter('money', array($this, 'money')),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('locales', [$this, 'getLocales']),
        ];
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param $seconds
     * @param bool $includeSeconds
     * @return string
     */
    public function duration($seconds, $includeSeconds = false)
    {
        $hour = floor($seconds / 3600);
        $minute = floor(($seconds / 60) % 60);

        $hour = $hour > 9 ? $hour : '0' . $hour;
        $minute = $minute > 9 ? $minute : '0' . $minute;

        if (!$includeSeconds) {
            return $hour . ':' . $minute . ' h';
        }

        $second = $seconds % 60;
        $second = $second > 9 ? $second : '0' . $second;

        return $hour . ':' . $minute  . ':' . $second . ' h';
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function money($amount, $currency = 'EUR')
    {
        return round($amount) . ' ' . Intl::getCurrencyBundle()->getCurrencySymbol($currency);
    }

    /**
     * Transforms the given Markdown content into HTML content.
     *
     *  @param string $content
     *
     * @return string
     */
    public function markdownToHtml($content)
    {
        return $this->parser->toHtml($content);
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.)
     *
     * @return array
     */
    public function getLocales()
    {
        $localeCodes = explode('|', $this->locales);

        $locales = [];
        foreach ($localeCodes as $localeCode) {
            $locales[] = ['code' => $localeCode, 'name' => Intl::getLocaleBundle()->getLocaleName($localeCode, $localeCode)];
        }

        return $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'kimai.extension';
    }
}
