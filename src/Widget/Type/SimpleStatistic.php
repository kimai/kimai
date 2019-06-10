<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

abstract class SimpleStatistic implements WidgetInterface
{
    public const DATA_TYPE_MONEY = 'money';
    public const DATA_TYPE_DURATION = 'duration';

    /**
     * @var string
     */
    protected $id = '';
    /**
     * @var string
     */
    protected $title;
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var mixed
     */
    protected $data;

    public function setId(string $id): SimpleStatistic
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setData($data): SimpleStatistic
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function setTitle(string $title): SimpleStatistic
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setOptions(array $options): SimpleStatistic
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }
}
