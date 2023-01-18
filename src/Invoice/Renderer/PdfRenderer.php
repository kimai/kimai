<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Export\Base\DispositionInlineInterface;
use App\Export\Base\DispositionInlineTrait;
use App\Export\ExportContext;
use App\Invoice\InvoiceFilename;
use App\Invoice\InvoiceModel;
use App\Utils\FileHelper;
use App\Utils\HtmlToPdfConverter;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class PdfRenderer extends AbstractTwigRenderer implements DispositionInlineInterface
{
    use DispositionInlineTrait;

    /**
     * @var HtmlToPdfConverter
     */
    private $converter;

    public function __construct(Environment $twig, HtmlToPdfConverter $converter)
    {
        parent::__construct($twig);
        $this->converter = $converter;
    }

    public function supports(InvoiceDocument $document): bool
    {
        return stripos($document->getFilename(), '.pdf.twig') !== false;
    }

    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $filename = new InvoiceFilename($model);

        $context = new ExportContext();
        $context->setOption('filename', $filename->getFilename());
        $context->setOption('setAutoTopMargin', 'pad');
        $context->setOption('setAutoBottomMargin', 'pad');
        $context->setOption('margin_top', '12');
        $context->setOption('margin_bottom', '8');

        $content = $this->renderTwigTemplate($document, $model, ['pdfContext' => $context]);
        $content = $this->converter->convertToPdf($content, $context->getOptions());

        $filename = $context->getOption('filename');
        if (empty($filename)) {
            $filename = new InvoiceFilename($model);
            $filename = $filename->getFilename();
        }

        $response = new Response($content);

        $filename = FileHelper::convertToAsciiFilename($filename);

        $disposition = $response->headers->makeDisposition($this->getDisposition(), $filename . '.pdf');

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
