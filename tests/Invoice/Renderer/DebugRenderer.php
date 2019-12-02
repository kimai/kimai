<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\InvoiceModel;
use App\Invoice\Renderer\AbstractRenderer;
use App\Invoice\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

class DebugRenderer extends AbstractRenderer implements RendererInterface
{
    /**
     * @return string[]
     */
    protected function getFileExtensions()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        return 'array';
    }

    /**
     * Render the given InvoiceDocument with the data from the InvoiceModel into a stupid array for testing only.
     *
     * @param InvoiceDocument $document
     * @param InvoiceModel $model
     * @return Response
     */
    public function render(InvoiceDocument $document, InvoiceModel $model): Response
    {
        $result = [
            'model' => $model->toArray(),
            'entries' => [],
        ];

        foreach ($model->getCalculator()->getEntries() as $entry) {
            $result['entries'][] = $model->itemToArray($entry);
        }

        return new Response(json_encode($result));
    }
}
