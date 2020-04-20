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
use App\Invoice\RendererInterface;
use PhpOffice\PhpWord\Escaper\Xml;
use PhpOffice\PhpWord\Exception\Exception as OfficeException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;

final class DocxRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        Settings::setOutputEscapingEnabled(false);

        $xmlEscaper = new Xml();
        $template = new TemplateProcessor($document->getFilename());

        foreach ($model->toArray() as $search => $replace) {
            $replace = $xmlEscaper->escape($replace);
            $replace = str_replace(PHP_EOL, '</w:t><w:br /><w:t xml:space="preserve">', $replace);

            $template->setValue($search, $replace);
        }

        try {
            $template->cloneRow('entry.description', \count($model->getCalculator()->getEntries()));
        } catch (OfficeException $ex) {
            try {
                $template->cloneRow('entry.row', \count($model->getCalculator()->getEntries()));
            } catch (OfficeException $ex) {
                @trigger_error(
                    sprintf('Invoice document (%s) did not contain a clone row, was that on purpose?', $document->getFilename())
                );
            }
        }

        $i = 1;
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $values = $model->itemToArray($entry);
            foreach ($values as $search => $replace) {
                $replace = $xmlEscaper->escape($replace);
                $replace = str_replace(PHP_EOL, '</w:t><w:br /><w:t xml:space="preserve">', $replace);

                $template->setValue($search . '#' . $i, $replace);
            }
            $i++;
        }

        $cacheFile = $template->save();

        clearstatcache(true, $cacheFile);

        $filename = $this->buildFilename($model) . '.' . $document->getFileExtension();

        return $this->getFileResponse(new Stream($cacheFile), $filename);
    }

    /**
     * @return string[]
     */
    protected function getFileExtensions()
    {
        return ['.docx'];
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
}
