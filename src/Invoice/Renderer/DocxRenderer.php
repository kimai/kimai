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
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;

class DocxRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * @param PhpWord $phpWord
     */
    protected function setPhpWordOptions(PhpWord $phpWord)
    {
        if (!extension_loaded('zip')) {
            \PhpOffice\PhpWord\Settings::setZipClass(\PhpOffice\PhpWord\Settings::PCLZIP);
        }

        /*
        \PhpOffice\PhpWord\Settings::setPdfRendererPath(__DIR__ . '/../../vendor/tecnickcom/tcpdf/');
        \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_TCPDF);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));
        */

        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Kimai 2');
        $properties->setDescription('Created with Kimai 2, the open-source time-tracking software! Get more information at www.kimai.org.');
        $properties->setCreated(time());
        $properties->setModified(time());
    }

    /**
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $filename = basename($document->getFilename());

        $template = new TemplateProcessor($document->getFilename());
        foreach ($this->modelToReplacer($model) as $key => $value) {
            $template->setValue($key, $value);
        }

        $template->cloneRow('entry.description', count($model->getCalculator()->getEntries()));
        $i = 1;
        foreach ($model->getCalculator()->getEntries() as $entry) {
            $values = $this->timesheetToArray($entry);
            foreach ($values as $search => $replace) {
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
