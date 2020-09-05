<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Constants;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\EntityWithMetaFields;
use App\Entity\Project;
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

    public function color(EntityWithMetaFields $entity): ?string
    {
        if ($entity instanceof Activity) {
            if (!empty($entity->getColor())) {
                return $entity->getColor();
            }

            if (null !== $entity->getProject()) {
                $entity = $entity->getProject();
            }
        }

        if ($entity instanceof Project) {
            if (!empty($entity->getColor())) {
                return $entity->getColor();
            }
            $entity = $entity->getCustomer();
        }

        if ($entity instanceof Customer) {
            if (!empty($entity->getColor())) {
                return $entity->getColor();
            }
        }

        return null;
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
