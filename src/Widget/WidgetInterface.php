<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

use App\Entity\User;
use Symfony\Component\Form\Form;

interface WidgetInterface
{
    public const COLOR_TODAY = 'green';
    public const COLOR_WEEK = 'blue';
    public const COLOR_MONTH = 'purple';
    public const COLOR_YEAR = 'yellow';
    public const COLOR_TOTAL = 'red';

    public const WIDTH_FULL = 4;
    public const WIDTH_LARGE = 3;
    public const WIDTH_HALF = 2;
    public const WIDTH_SMALL = 1;

    public const HEIGHT_MAXIMUM = 6;
    public const HEIGHT_LARGE = 5;
    public const HEIGHT_MEDIUM = 3;
    public const HEIGHT_SMALL = 1;

    /**
     * Returns a unique ID for this widget.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns the widget title (must be non-empty).
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the height for this widget.
     *
     * @return int
     */
    public function getHeight(): int;

    /**
     * Returns the width for this widget.
     *
     * @return int
     */
    public function getWidth(): int;

    /**
     * Injects the current user.
     *
     * @param User $user
     * @return void
     */
    public function setUser(User $user): void;

    /**
     * Returns the widget data, to be used in the frontend rendering.
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
     * @param string|bool|int $value
     */
    public function setOption(string $name, string|bool|int $value): void;

    /**
     * Return a list of granted syntax string.
     * If ANY of the given permission strings matches, access is granted.
     *
     * @return string[]
     */
    public function getPermissions(): array;

    /**
     * Returns the template, which is used to render the widget.
     *
     * @return string
     */
    public function getTemplateName(): string;

    /**
     * Whether this widget can be configured with options.
     *
     * @return bool
     */
    public function hasForm(): bool;

    /**
     * A form to edit the widget options or null, if it can't be configured.
     *
     * @return Form|null
     */
    public function getForm(): ?Form;

    /**
     * Whether this is a widget that is supposed to be selectable by the end-user.
     *
     * @return bool
     */
    public function isInternal(): bool;
}
