<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Pdf;

use App\Utils\FileHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

trait PdfRendererTrait
{
    private bool $inline = false;

    public function setDispositionInline(bool $useInlineDisposition): void
    {
        $this->inline = $useInlineDisposition;
    }

    protected function createPdfResponse(string $content, PdfContext $context): Response
    {
        $filename = $context->getOption('filename');
        if (empty($filename)) {
            throw new \Exception('Empty PDF filename given');
        }
        $filename = FileHelper::convertToAsciiFilename($filename);

        $response = new Response($content);

        $disposition = $this->inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $disposition = $response->headers->makeDisposition($disposition, $filename . '.pdf');

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
