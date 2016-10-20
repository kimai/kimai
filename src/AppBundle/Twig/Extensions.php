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

/**
 * This Twig extension adds new filters:
 * - 'md2html' to transform Markdown contents into HTML contents
 * - 'gmdate' to transform timestamps into a gmdate() formatted string
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
            new \Twig_SimpleFilter('gmdate', array($this, 'gmdate')),
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
     * Transform a timestamp into a gmdate() formatted string.
     *
     * @param $seconds
     * @param string $format
     * @return false|string
     */
    public function gmdate($seconds, $format = 'H:i')
    {
        return gmdate($format, $seconds);
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
