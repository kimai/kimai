<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

abstract class AbstractWidgetType implements WidgetInterface
{
    /**
     * @var string
     */
    protected $id = '';
    /**
     * @var string
     */
    protected $title = '';
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var mixed
     */
    protected $data;

    public function setId(string $id): AbstractWidgetType
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setData($data): AbstractWidgetType
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $options
     * @return mixed|null
     */
    public function getData(array $options = [])
    {
        return $this->data;
    }

    public function setTitle(string $title): AbstractWidgetType
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setOptions(array $options): AbstractWidgetType
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        if (\array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    public function getOptions(array $options = []): array
    {
        return array_merge($this->options, $options);
    }
}
