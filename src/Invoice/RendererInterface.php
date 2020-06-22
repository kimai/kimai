<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\InvoiceDocument;
use Symfony\Component\HttpFoundation\Response;

interface RendererInterface
{
    /**
     * Checks whether the given InvoiceDocument can be rendered.
     *
     * @param InvoiceDocument $document
     * @return bool
     */
    public function supports(InvoiceDocument $document): bool;

    /**
     * Render the given InvoiceDocument with the data from the InvoiceModel.
     *
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response;
}
