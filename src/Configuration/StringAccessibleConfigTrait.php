<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\Configuration;

trait StringAccessibleConfigTrait
{
    /**
     * @var array
     */
    protected $settings;
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
        $this->settings = $settings;
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

        // this foreach should be replaced by a better piece of code,
        // especially the pointers could be a problem in the future
        foreach ($this->getConfigurations($this->repository) as $configuration) {
            $temp = explode('.', $configuration->getName());
            $array = &$this->settings;
            if ($temp[0] === $this->getPrefix()) {
                $temp = \array_slice($temp, 1);
            }
            foreach ($temp as $key2) {
                if (!\array_key_exists($key2, $array)) {
                    // unknown values will silently be skipped
                    continue 2;
                }
                if (\is_array($array[$key2])) {
                    $array = &$array[$key2];
                } elseif (\is_bool($array[$key2])) {
                    $array[$key2] = (bool) $configuration->getValue();
                } elseif (\is_int($array[$key2])) {
                    $array[$key2] = (int) $configuration->getValue();
                } else {
                    $array[$key2] = $configuration->getValue();
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
        $length = \strlen($prefix);

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

        if (!\array_key_exists($search, $config)) {
            throw new \InvalidArgumentException('Unknown config: ' . $key);
        }

        if (\is_array($config[$search]) && !empty($keys)) {
            return $this->get(implode('.', $keys), $config[$search]);
        }

        return $config[$search];
    }

    /**
     * @return bool
     */
    public function offsetExists($offset)
    {
        try {
            $this->find($offset);
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
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
        throw new \BadMethodCallException('SystemBundleConfiguration does not support offsetSet()');
    }

    /**
     * @param mixed $offset
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('SystemBundleConfiguration does not support offsetUnset()');
    }
}
