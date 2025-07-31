<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\InvoiceDocument;
use Symfony\Contracts\EventDispatcher\Event;

final class InvoiceDocumentsEvent extends Event
{
    /**
     * Maximum amount of allowed invoice documents.
     */
    private int $maximum = 99;

    /**
     * @param InvoiceDocument[] $documents
     */
    public function __construct(private array $documents)
    {
    }

    /**
     * @return InvoiceDocument[]
     */
    public function getInvoiceDocuments(): array
    {
        return $this->documents;
    }

    public function addInvoiceDocuments(InvoiceDocument $document): void
    {
        $this->documents[] = $document;
    }

    /**
     * @param InvoiceDocument[] $documents
     */
    public function setInvoiceDocuments(array $documents): void
    {
        $this->documents = $documents;
    }

    /**
     * @CloudRequired
     */
    public function setMaximumAllowedDocuments(int $max): void
    {
        $this->maximum = $max;
    }

    public function getMaximumAllowedDocuments(): int
    {
        return $this->maximum;
    }
}
