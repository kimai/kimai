<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Entity\InvoiceDocument;
use App\Invoice\Renderer\RendererTrait;
use App\Invoice\RendererInterface;
use App\Model\InvoiceModel;
use Symfony\Component\HttpFoundation\Response;

class DebugRenderer implements RendererInterface
{
    use RendererTrait;

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
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedDateTime(\DateTime $date)
    {
        return $date->format('d.m.Y');
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedTime(\DateTime $date)
    {
        return $date->format('H:i');
    }

    /**
     * @param mixed $amount
     * @return mixed
     */
    protected function getFormattedMoney($amount)
    {
        return $amount;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    protected function getFormattedMonthName(\DateTime $date)
    {
        return $date->format('m');
    }

    /**
     * @param mixed $seconds
     * @return mixed
     */
    protected function getFormattedDuration($seconds)
    {
        return $seconds;
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
            'model' => $this->modelToReplacer($model),
            'entries' => [],
        ];

        foreach ($model->getCalculator()->getEntries() as $entry) {
            $result['entries'][] = $this->timesheetToArray($entry);
        }

        return new Response(json_encode($result));
    }
}
