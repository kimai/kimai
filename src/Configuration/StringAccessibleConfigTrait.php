<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Repository\ConfigurationRepository;

trait StringAccessibleConfigTrait
{
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var ConfigurationRepository
     */
    protected $repository;
    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param array $settings
     */
    public function __construct(ConfigurationRepository $repository, array $settings)
    {
        $this->repository = $repository;
        $this->settings = $settings;
    }

    protected function prepare()
    {
        if ($this->initialized) {
            return;
        }

        // this foreach should be replaced by a better piece of code,
        // especially the pointers could be a problem in the future
        foreach ($this->repository->getAllConfigurations() as $key => $value) {
            $temp = explode('.', $key);
            $array = &$this->settings;
            if ($temp[0] === $this->getPrefix()) {
                $temp = array_slice($temp, 1);
            }
            foreach ($temp as $key2) {
                if (!isset($array[$key2])) {
                    continue 2;
                }
                if (is_array($array[$key2])) {
                    $array = &$array[$key2];
                } elseif (is_bool($array[$key2])) {
                    $array[$key2] = (bool) $value;
                } elseif (is_int($array[$key2])) {
                    $array[$key2] = (int) $value;
                } else {
                    $array[$key2] = $value;
                }
            }
        }

        $this->initialized = true;
    }

    /**
     * @return string
     */
    abstract protected function getPrefix(): string;

    /**
     * @param string $key
     * @return mixed
     */
    public function find(string $key)
    {
        $this->prepare();
        $prefix = $this->getPrefix() . '.';
        $length = strlen($prefix);

        if (substr($key, 0, $length) === $prefix) {
            $key = substr($key, $length);
        }

        return $this->get($key, $this->settings);
    }

    /**
     * @param string $key
     * @param array $config
     * @return mixed
     */
    private function get(string $key, array $config)
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
