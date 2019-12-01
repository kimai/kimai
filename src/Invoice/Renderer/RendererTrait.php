<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Invoice\InvoiceItem;
use App\Invoice\InvoiceModel;

trait RendererTrait
{
    /**
     * @var InvoiceModel
     */
    private $model;

    /**
     * @deprecated since 1.6.2 - will be removed with 2.0
     * @param InvoiceModel $model
     * @return array
     */
    protected function modelToReplacer(InvoiceModel $model)
    {
        @trigger_error('modelToReplacer() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->model = $model;

        return $model->toArray();
    }

    /**
     * @deprecated since 1.3 - will be removed with 2.0
     */
    protected function timesheetToArray(InvoiceItem $invoiceItem): array
    {
        @trigger_error('timesheetToArray() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->model->itemToArray($invoiceItem);
    }

    /**
     * @deprecated since 1.6.2 - will be removed with 2.0
     * @param InvoiceItem $invoiceItem
     * @return array
     */
    protected function invoiceItemToArray(InvoiceItem $invoiceItem): array
    {
        @trigger_error('invoiceItemToArray() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->model->itemToArray($invoiceItem);
    }
}
