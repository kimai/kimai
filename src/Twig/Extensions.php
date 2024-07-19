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
use Twig\TwigTest;

final class Extensions extends AbstractExtension
{
    public const REPORT_DATE = 'Y-m-d';

    public function getFilters(): array
    {
        return [
            new TwigFilter('report_date', [$this, 'formatReportDate']),
            new TwigFilter('docu_link', [$this, 'documentationLink']),
            new TwigFilter('multiline_indent', [$this, 'multilineIndent']),
            new TwigFilter('color', [$this, 'color']),
            new TwigFilter('font_contrast', [$this, 'calculateFontContrastColor']),
            new TwigFilter('default_color', [$this, 'defaultColor']),
            new TwigFilter('nl2str', [$this, 'replaceNewline'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('report_date', [$this, 'buildReportDate']),
            new TwigFunction('class_name', [$this, 'getClassName']),
            new TwigFunction('iso_day_by_name', [$this, 'getIsoDayByName']),
            new TwigFunction('random_color', [$this, 'randomColor']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('number', function ($value): bool {
                return !\is_string($value) && is_numeric($value);
            }),
        ];
    }

    public function buildReportDate(string|int $year, string|int $month = 1, string|int $day = 1): \DateTimeImmutable
    {
        if (\is_string($month)) {
            $month = (int) $month;
        }
        if ($month > 12 || $month < 1) {
            throw new \InvalidArgumentException('Unknown month: ' . $month);
        }
        if ($month < 10) {
            $month = '0' . $month;
        }

        if (\is_string($day)) {
            $day = (int) $day;
        }
        if ($day > 31 || $day < 1) {
            throw new \InvalidArgumentException('Unknown day: ' . $day);
        }
        if ($day < 10) {
            $day = '0' . $day;
        }

        if (\is_string($year)) {
            $year = (int) $year;
        }
        if ($year < 1980 || $year > 2100) {
            throw new \InvalidArgumentException('Unknown year: ' . $year);
        }

        return \DateTimeImmutable::createFromFormat('Y-m-d', $year . '-' . $month . '-' . $day); // @phpstan-ignore-line
    }

    public function formatReportDate(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format(self::REPORT_DATE);
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

    public function randomColor(?string $input = null): string
    {
        return (new Color())->getRandom($input);
    }

    public function calculateFontContrastColor(string $color): string
    {
        return (new Color())->getFontContrastColor($color);
    }

    public function defaultColor(?string $color = null): string
    {
        return $color ?? Constants::DEFAULT_COLOR;
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

    public function replaceNewline($input, string $newline)
    {
        if (!\is_string($input)) {
            return $input;
        }

        return str_replace(["\r\n", "\n", "\r"], $newline, $input);
    }
}
