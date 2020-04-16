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
use App\Invoice\RendererInterface;
use App\Utils\HtmlToPdfConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

final class PdfRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var HtmlToPdfConverter
     */
    private $converter;

    public function __construct(Environment $twig, HtmlToPdfConverter $converter)
    {
        $this->twig = $twig;
        $this->converter = $converter;
    }

    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.pdf.twig') !== false;
    }

    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $content = $this->twig->render('@invoice/' . basename($document->getFilename()), [
            'model' => $model
        ]);
        $content = $this->converter->convertToPdf($content);
        $filename = (string) new InvoiceFilename($model);

        $response = new Response($content);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename . '.pdf');

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
