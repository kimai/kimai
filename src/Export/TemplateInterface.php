<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

interface TemplateInterface
{
    public function getId(): string;

    public function getTitle(): string;

    /**
     * @return array<int, string>
     */
    public function getColumns(): array;

    public function getLocale(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;
}
