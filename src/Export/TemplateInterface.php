<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Repository\Query\TimesheetQuery;

interface TemplateInterface
{
    public function getId(): string;

    public function getTitle(): string;

    /**
     * @return array<int, string>
     */
    public function getColumns(TimesheetQuery $query): array;

    public function getLocale(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;
}
