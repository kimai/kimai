<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\SystemConfiguration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Configuration extends AbstractExtension
{
    private $configuration;
    private $cache = [];

    public function __construct(SystemConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('config', [$this, 'get']),
        ];
    }

    public function get(string $name)
    {
        if (\array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $value = $this->configuration->find($name);
        $this->cache[$name] = $value;

        return $value;
    }

    public function __call($name, $arguments)
    {
        if (\array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $checks = ['is' . $name, 'get' . $name, 'has' . $name, $name];

        foreach ($checks as $methodName) {
            if (method_exists($this->configuration, $methodName)) {
                $value = \call_user_func([$this->configuration, $methodName], $arguments);
                $this->cache[$name] = $value;

                return $value;
            }
        }

        return $this->configuration->find($name);
    }
}
