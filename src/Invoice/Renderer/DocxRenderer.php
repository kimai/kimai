<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\RendererInterface;
use App\Model\InvoiceModel;
use PhpOffice\PhpWord\Escaper\Xml;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;

class DocxRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);

        $filename = basename($document->getFilename());

        $xmlEscaper = new Xml();
        $template = new TemplateProcessor($document->getFilename());

        foreach ($this->modelToReplacer($model) as $search => $replace) {
            $replace = $xmlEscaper->escape($replace);
            $replace = str_replace(PHP_EOL, '</w:t><w:br /><w:t xml:space="preserve">', $replace);

            $template->setValue($search, $replace);
        }

        $template->cloneRow('entry.description', count($model->getCalculator()->getEntries()));
        $i = 1;
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $values = $this->timesheetToArray($entry);
            foreach ($values as $search => $replace) {
                $replace = $xmlEscaper->escape($replace);
                $replace = str_replace(PHP_EOL, '</w:t><w:br /><w:t xml:space="preserve">', $replace);

                $template->setValue($search . '#' . $i, $replace);
            }
            $i++;
        }

        $cacheFile = $template->save();

        clearstatcache(true, $cacheFile);

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
