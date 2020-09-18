<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Constants;
use App\Entity\EntityWithMetaFields;
use App\Utils\Color;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('docu_link', [$this, 'documentationLink']),
            new TwigFilter('multiline_indent', [$this, 'multilineIndent']),
            new TwigFilter('color', [$this, 'color']),
            new TwigFilter('font_contrast', [$this, 'calculateFontContrastColor']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('class_name', [$this, 'getClassName']),
            new TwigFunction('iso_day_by_name', [$this, 'getIsoDayByName']),
        ];
    }

    public function getIsoDayByName(string $weekDay): int
    {
        $key = array_search(
            strtolower($weekDay),
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
        );

        if (false === $key) {
            return 1;
        }

        return ++$key;
    }

    /**
     * Returns null instead of the default color if $defaultColor is not set to true.
     *
     * @param EntityWithMetaFields $entity
     * @return string|null
     */
    public function color(EntityWithMetaFields $entity, bool $defaultColor = false): ?string
    {
        return (new Color())->getColor($entity, $defaultColor);
    }

    public function calculateFontContrastColor(string $color): string
    {
        return (new Color())->getFontContrastColor($color);
    }

    /**
     * @param object $object
     * @return null|string
     */
    public function getClassName($object): ?string
    {
        if (!\is_object($object)) {
            return null;
        }

        return \get_class($object);
    }

    public function multilineIndent(?string $string, string $indent): string
    {
        if (null === $string || '' === $string) {
            return '';
        }

        $parts = [];

        foreach (explode("\r\n", $string) as $part) {
            foreach (explode("\n", $part) as $tmp) {
                $parts[] = $tmp;
            }
        }

        $parts = array_map(function ($part) use ($indent) {
            return $indent . $part;
        }, $parts);

        return implode(PHP_EOL, $parts);
    }

    public function documentationLink(?string $url = ''): string
    {
        return Constants::HOMEPAGE . '/documentation/' . $url;
    }
}
