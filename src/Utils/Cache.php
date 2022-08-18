<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Contracts\Cache\CacheInterface;

final class Cache implements CacheInterface
{
    private string $namespace = 'kimai';

    public function __construct(private CacheInterface $kimai)
    {
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @CloudRequired
     * @param string $namespace
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null): mixed
    {
        return $this->kimai->get($this->namespace . $key, $callback, $beta, $metadata);
    }

    public function delete(string $key): bool
    {
        return $this->kimai->delete($this->namespace . $key);
    }
}
