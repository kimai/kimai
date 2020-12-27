<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated since 1.13 - will be removed with 2.0
 */
class DeprecatedTimesheetExportRenderer implements TimesheetExportRenderer, TimesheetExportInterface
{
    /**
     * @var TimesheetExportInterface
     */
    private $renderer;

    public function __construct(TimesheetExportInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function create(Export $export): Response
    {
        @trigger_error(
            sprintf('TimesheetExportInterface::render() in %s is deprecated and will be removed with 2.0', \get_class($this->renderer)),
            E_USER_DEPRECATED
        );

        /** @var Timesheet[] $items */
        $items = $export->getItems();

        return $this->renderer->render($items, $export->getQuery());
    }

    public function getId(): string
    {
        return $this->renderer->getId();
    }

    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        return $this->renderer->render($timesheets, $query);
    }
}
