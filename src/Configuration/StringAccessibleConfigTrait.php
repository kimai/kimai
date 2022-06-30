<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\Configuration;

/**
 * @internal do NOT use this trait, but access your configs via SystemConfiguration
 */
trait StringAccessibleConfigTrait
{
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var array
     */
    protected $original;
    /**
     * @var ConfigLoaderInterface
     */
    protected $repository;
    /**
     * @var bool
     */
    protected $initialized = false;

    public function __construct(ConfigLoaderInterface $repository, array $settings)
    {
        $this->repository = $repository;
        $this->original = $this->settings = $settings;
    }

    /**
     * @param ConfigLoaderInterface $repository
     * @return Configuration[]
     */
    protected function getConfigurations(ConfigLoaderInterface $repository): array
    {
        return $repository->getConfiguration($this->getPrefix());
    }

    protected function prepare()
    {
        if ($this->initialized) {
            return;
        }

        foreach ($this->getConfigurations($this->repository) as $configuration) {
            $this->set($configuration->getName(), $configuration->getValue());
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
    public function default(string $key)
    {
        $key = $this->prepareSearchKey($key);

        return $this->get($key, $this->original);
    }

    /**
     * @param string $key
     * @return string|int|bool|float|null|array
     */
    public function find(string $key)
    {
        $this->prepare();
        $key = $this->prepareSearchKey($key);

        return $this->get($key, $this->settings);
    }

    private function prepareSearchKey(string $key): string
    {
        $prefix = $this->getPrefix() . '.';
        $length = \strlen($prefix);

        if (substr($key, 0, $length) === $prefix) {
            $key = substr($key, $length);
        }

        return $key;
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

        if (!\array_key_exists($search, $config)) {
            return null;
        }

        if (\is_array($config[$search]) && !empty($keys)) {
            return $this->get(implode('.', $keys), $config[$search]);
        }

        return $config[$search];
    }

    public function has(string $key): bool
    {
        $this->prepare();
        $key = $this->prepareSearchKey($key);

        $keys = explode('.', $key);
        $search = array_shift($keys);

        if (!\array_key_exists($search, $this->settings)) {
            return false;
        }

        return true;
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param $offset
     * @return array|bool|float|int|string|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->find($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('SystemBundleConfiguration does not support offsetUnset()');
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @see https://github.com/divineomega/array_undot
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    private function set(string $key, $value): array
    {
        $array = &$this->settings;
        $keys = explode('.', $key);
        while (\count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !\is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $k = array_shift($keys);

        if (\array_key_exists($k, $array)) {
            if (\is_bool($array[$k])) {
                $value = (bool) $value;
            } elseif (\is_int($array[$k])) {
                $value = (int) $value;
            }
        }

        $array[$k] = $value;

        return $array;
    }
}
