<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Export\ServiceExport;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TimesheetExtension extends AbstractExtension
{
    /**
     * @var ServiceExport
     */
    private $service;

    public function __construct(ServiceExport $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('timesheet_exporter', [$this, 'getTimesheetExporter'], []),
        ];
    }

    public function getTimesheetExporter(): array
    {
        $ids = [];
        foreach ($this->service->getTimesheetExporter() as $exporter) {
            $ids[] = $exporter->getId();
        }

        return $ids;
    }
}
