<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

trait StringAccessibleConfigTrait
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return string
     */
    protected abstract function getPrefix(): string;

    /**
     * @param string $key
     * @return mixed
     */
    public function find(string $key)
    {
        $prefix = $this->getPrefix() . '.';
        $length = strlen($prefix);

        if (substr($key, 0, $length) ===  $prefix) {
            $key = substr($key, $length);
        }

        return $this->get($key, $this->settings);
    }

    /**
     * @param string $key
     * @param array $config
     * @return mixed
     */
    protected function get(string $key, array $config)
    {
        $keys = explode('.', $key);
        $search = array_shift($keys);

        if (!isset($config[$search])) {
            throw new \InvalidArgumentException('Unknown config: ' . $key);
        }

        if (is_array($config[$search]) && !empty($keys)) {
            return $this->get(implode('.', $keys), $config[$search]);
        }

        return $config[$search];
    }
}
