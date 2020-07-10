<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceModel;
use Symfony\Component\HttpFoundation\Response;

final class TwigRenderer extends AbstractTwigRenderer
{
    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.html.twig') !== false;
    }

    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $content = $this->renderTwigTemplate($document, $model);

        $response = new Response();
        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }
}
