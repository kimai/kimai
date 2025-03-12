<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * No BC promise!
 * Use AbstractWidget to get a BC safe base class.
 */
#[AutoconfigureTag]
interface WidgetInterface
{
    /** @deprecated */
    public const COLOR_TODAY = 'green';
    /** @deprecated */
    public const COLOR_WEEK = 'blue';
    /** @deprecated */
    public const COLOR_MONTH = 'purple';
    /** @deprecated */
    public const COLOR_YEAR = 'yellow';
    /** @deprecated */
    public const COLOR_TOTAL = 'red';

    public const WIDTH_FULL = 4;
    public const WIDTH_NORMAL = 2;
    public const WIDTH_SMALL = 1;

    /** @deprecated */
    public const WIDTH_LARGE = 2;
    /** @deprecated */
    public const WIDTH_HALF = 2;
    /** @deprecated */
    public const HEIGHT_MAXIMUM = 6;
    /** @deprecated */
    public const HEIGHT_LARGE = 5;
    /** @deprecated */
    public const HEIGHT_MEDIUM = 3;
    /** @deprecated */
    public const HEIGHT_SMALL = 1;

    /**
     * Returns a unique ID for this widget.
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * Returns the widget title.
     * @return non-empty-string
     */
    public function getTitle(): string;

    /**
     * Returns the translation domain used by this widget.
     */
    public function getTranslationDomain(): string;

    /**
     * Returns the width for this widget.
     */
    public function getWidth(): int;

    /**
     * Injects the current user.
     */
    public function setUser(User $user): void;

    /**
     * Returns the widget data, to be used in the frontend rendering.
     *
     * If your widget relies on options to dynamically change the result data,
     * make sure that the given $options will overwrite the internal option for
     * this one call.
     *
     * @param array<string, string|bool|int|float> $options
     * @return mixed|null
     */
    public function getData(array $options = []): mixed;

    /**
     * Returns all widget options, which should be configurable in the frontend.
     *
     * Kimai supports the following named options by default:
     * - daterange: see AbstractWidget::getDateRangeColor() and AbstractWidget::getDateRangeTitle()
     *
     * Do not return internal options from this method.
     *
     * @return array<string, string|bool|int|float>
     */
    public function getOptions(): array;

    /**
     * Sets one widget option, overwriting the default value.
     */
    public function setOption(string $name, string|bool|int|float $value): void;

    /**
     * Return a list of granted syntax string.
     * If ANY of the given permission strings matches, access is granted.
     *
     * @return string[]
     */
    public function getPermissions(): array;

    /**
     * Returns the template, which is used to render the widget.
     */
    public function getTemplateName(): string;

    /**
     * Whether this is a widget that is supposed to be selectable by the end-user.
     */
    public function isInternal(): bool;
}
