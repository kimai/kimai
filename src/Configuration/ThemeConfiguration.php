<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @internal use SystemConfiguration as access point in your code - in Twig use config('theme.branding.company') instead
 */
final class ThemeConfiguration implements \ArrayAccess
{
    private $systemConfiguration;

    public function __construct(SystemConfiguration $systemConfiguration)
    {
        $this->systemConfiguration = $systemConfiguration;
    }

    public function offsetExists($offset): bool
    {
        return $this->systemConfiguration->has('theme.' . $offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->systemConfiguration->find('theme.' . $offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('ThemeConfiguration does not support offsetSet()');
    }

    /**
     * @param mixed $offset
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('ThemeConfiguration does not support offsetUnset()');
    }
}
