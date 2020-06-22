<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceModel;
use App\Invoice\RendererInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

final class InvoicePostRenderEvent extends Event
{
    /**
     * @var InvoiceModel
     */
    private $model;
    /**
     * @var InvoiceDocument
     */
    private $document;
    /**
     * @var RendererInterface
     */
    private $renderer;
    /**
     * @var Response
     */
    private $response;

    public function __construct(InvoiceModel $model, InvoiceDocument $document, RendererInterface $renderer, Response $response)
    {
        $this->model = $model;
        $this->document = $document;
        $this->renderer = $renderer;
        $this->response = $response;
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

    public function getResponse(): Response
    {
        return $this->response;
    }
}
