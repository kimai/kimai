<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Export\ServiceExport;
use Twig\Extension\RuntimeExtensionInterface;

final class ExporterExtension implements RuntimeExtensionInterface
{
    /**
     * @var ServiceExport
     */
    private $service;

    public function __construct(ServiceExport $service)
    {
        $this->service = $service;
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
