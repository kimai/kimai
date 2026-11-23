<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\ExportableItem;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;

/**
 * A calculator that sums up the invoice item records by project and activity.
 */
final class ProjectActivityInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    public function getIdentifiers(ExportableItem $invoiceItem): array
    {
        return [
           $invoiceItem->getProject()?->getId(),
           $invoiceItem->getActivity()?->getId()
        ];
    }

    protected function mergeSumInvoiceItem(InvoiceItem $invoiceItem, ExportableItem $entry): void
    {
        $project = $entry->getProject();
        if ($project !== null) {
            $invoiceItem->setDescription($project->getInvoiceText() ?? $project->getName());
        }
    }

    public function getId(): string
    {
        return 'project_activity';
    }
}
