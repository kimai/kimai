<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

interface DragAndDropSource
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getRoute(): string;

    /**
     * @return array<string, string>
     */
    public function getRouteParams(): array;

    /**
     * @return array<string, string>
     */
    public function getRouteReplacer(): array;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return DragAndDropEntry[]
     */
    public function getEntries(): array;

    /**
     * If you want to customize the item rendering, you have to return a path to your include here.
     *
     * @return string|null
     */
    public function getBlockInclude(): ?string;
}
