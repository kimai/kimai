<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @internal might be deprecated in the future, use SystemConfiguration instead
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

    /**
     * @return mixed
     */
    public function offsetGet($offset)
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

    /**
     * @deprecated since 1.15
     */
    public function isAllowTagCreation(): bool
    {
        return (bool) $this->offsetGet('tags_create');
    }

    /**
     * @deprecated since 1.15
     */
    public function getTitle(): ?string
    {
        $title = $this->offsetGet('branding.title');
        if (null === $title) {
            return null;
        }

        return (string) $title;
    }
}
