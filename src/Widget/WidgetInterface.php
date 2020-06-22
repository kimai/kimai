<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

interface WidgetInterface
{
    /**
     * Returns a unique ID for this widget.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns the widget title.
     *
     * If no title is necessary, return an empty string.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the widgets data, to be used in the frontend rendering.
     *
     * If your widget relies on options to dynamically change the result data,
     * make sure that the given $options will overwrite the internal option for
     * this one call.
     *
     * @param array $options
     * @return mixed|null
     */
    public function getData(array $options = []);

    /**
     * Returns all widget options to be used in the frontend.
     *
     * The given $options are not meant to be persisted, but only to
     * overwrite the default values one time.
     *
     * You can validate the options or simply return:
     * return array_merge($this->options, $options);
     *
     * @param array $options
     * @return array
     */
    public function getOptions(array $options = []): array;

    /**
     * Sets one widget option, both for internal use and for frontend rendering.
     *
     * The given option should be persisted and permanently overwrite the internal option.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void;
}
