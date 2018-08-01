<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class WidgetRow
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var string[]
     */
    protected $widgets = [];

    /**
     * @param string $id
     * @param string $title
     */
    public function __construct(string $id, string $title = '')
    {
        $this->id = $id;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * @param string $templateString
     * @return $this
     */
    public function add(string $templateString)
    {
        $this->widgets[] = $templateString;

        return $this;
    }
}
