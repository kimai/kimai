<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;

/**
 * A blocking approach for Twig templates.
 */
final class ForbiddenPolicy implements SecurityPolicyInterface
{
    /** @var array<string, array<string>> */
    private array $forbiddenMethods = [];

    /**
     * @param array<string> $forbiddenTags
     * @param array<string> $forbiddenFilters
     * @param array<string, array<string>> $forbiddenMethods
     * @param array<string, array<string>> $forbiddenProperties
     * @param array<string> $forbiddenFunctions
     */
    public function __construct(
        private array $forbiddenTags = [],
        private array $forbiddenFilters = [],
        array $forbiddenMethods = [],
        private array $forbiddenProperties = [],
        private array $forbiddenFunctions = []
    )
    {
        $this->forbiddenMethods = [];
        foreach ($forbiddenMethods as $class => $m) {
            $this->forbiddenMethods[$class] = array_map(function ($value) { return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'); }, \is_array($m) ? $m : [$m]);
        }
    }

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (\in_array($tag, $this->forbiddenTags)) {
                throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (\in_array($filter, $this->forbiddenFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (\in_array($function, $this->forbiddenFunctions)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        $forbidden = false;
        $method = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        foreach ($this->forbiddenMethods as $class => $methods) {
            if ($obj instanceof $class) {
                $forbidden = \in_array($method, $methods);

                break;
            }
        }

        if ($forbidden) {
            $class = \get_class($obj);
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
        }
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $forbidden = false;
        foreach ($this->forbiddenProperties as $class => $properties) {
            if ($obj instanceof $class) {
                $forbidden = \in_array($property, \is_array($properties) ? $properties : [$properties]);

                break;
            }
        }

        if ($forbidden) {
            $class = \get_class($obj);
            throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
        }
    }
}
