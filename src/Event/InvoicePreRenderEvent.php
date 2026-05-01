<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Invoice\InvoiceModel;
use App\Invoice\RendererInterface;
use App\Model\InvoiceDocument;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Triggered right before an invoice is rendered.
 *
 * You can use this event to:
 * - add invoice hydrator
 * - read and change invoice model
 * - change tax rates
 */
final class InvoicePreRenderEvent extends Event
{
    public function __construct(
        private readonly InvoiceModel $model,
        private readonly InvoiceDocument $document,
        private readonly RendererInterface $renderer
    )
    {
    }

    public function getModel(): InvoiceModel
    {
        return $this->model;
    }

    public function getDocument(): InvoiceDocument
    {
        return $this->document;
    }

    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }
}
