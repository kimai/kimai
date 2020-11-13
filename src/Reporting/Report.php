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
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $route;

    public function __construct(string $id, string $route, string $label)
    {
        $this->id = $id;
        $this->route = $route;
        $this->label = $label;
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
}
