<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

final class Report implements ReportInterface
{
    public function __construct(private string $id, private string $route, private string $label, private string $reportIcon)
    {
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getReportIcon(): string
    {
        return $this->reportIcon;
    }
}
