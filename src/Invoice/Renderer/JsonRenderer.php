<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceFilename;
use App\Invoice\InvoiceModel;
use Symfony\Component\HttpFoundation\Response;

final class JsonRenderer extends AbstractTwigRenderer implements DispositionInlineInterface
{
    use DispositionInlineTrait;

    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.json.twig') !== false;
    }

    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $content = $this->renderTwigTemplate($document, $model);
        $filename = (string) new InvoiceFilename($model);

        $response = new Response($content);

        $disposition = $response->headers->makeDisposition($this->getDisposition(), $filename . '.json');

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
