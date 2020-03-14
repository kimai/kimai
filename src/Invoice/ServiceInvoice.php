<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Invoice;
use App\Entity\InvoiceDocument;
use App\Event\InvoicePostRenderEvent;
use App\Repository\InvoiceDocumentRepository;
use App\Utils\FileHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Service to manage invoice dependencies.
 */
final class ServiceInvoice
{
    /**
     * @var CalculatorInterface[]
     */
    private $calculator = [];
    /**
     * @var RendererInterface[]
     */
    private $renderer = [];
    /**
     * @var NumberGeneratorInterface[]
     */
    private $numberGenerator = [];
    /**
     * @var array InvoiceItemRepositoryInterface[]
     */
    private $invoiceItemRepositories = [];
    /**
     * @var InvoiceDocumentRepository
     */
    private $documents;
    /**
     * @var FileHelper
     */
    private $fileHelper;

    public function __construct(InvoiceDocumentRepository $repository, FileHelper $fileHelper)
    {
        $this->documents = $repository;
        $this->fileHelper = $fileHelper;
    }

    public function addNumberGenerator(NumberGeneratorInterface $generator): ServiceInvoice
    {
        $this->numberGenerator[] = $generator;

        return $this;
    }

    /**
     * @return NumberGeneratorInterface[]
     */
    public function getNumberGenerator(): array
    {
        return $this->numberGenerator;
    }

    public function getNumberGeneratorByName(string $name): ?NumberGeneratorInterface
    {
        foreach ($this->getNumberGenerator() as $generator) {
            if ($generator->getId() === $name) {
                return $generator;
            }
        }

        return null;
    }

    public function addCalculator(CalculatorInterface $calculator): ServiceInvoice
    {
        $this->calculator[] = $calculator;

        return $this;
    }

    /**
     * @return CalculatorInterface[]
     */
    public function getCalculator(): array
    {
        return $this->calculator;
    }

    public function getCalculatorByName(string $name): ?CalculatorInterface
    {
        foreach ($this->getCalculator() as $calculator) {
            if ($calculator->getId() === $name) {
                return $calculator;
            }
        }

        return null;
    }

    public function getDocumentByName(string $name): ?InvoiceDocument
    {
        return $this->documents->findByName($name);
    }

    /**
     * Returns an array of invoice renderer, which will consist of a unique name and a controller action.
     *
     * @return InvoiceDocument[]
     */
    public function getDocuments(): array
    {
        return $this->documents->findAll();
    }

    public function addRenderer(RendererInterface $renderer): ServiceInvoice
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * Returns an array of invoice renderer.
     *
     * @return RendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }

    /**
     * @return InvoiceItemRepositoryInterface[]
     */
    public function getInvoiceItemRepositories(): array
    {
        return $this->invoiceItemRepositories;
    }

    public function addInvoiceItemRepository(InvoiceItemRepositoryInterface $invoiceItemRepository): ServiceInvoice
    {
        $this->invoiceItemRepositories[] = $invoiceItemRepository;

        return $this;
    }

    private function getInvoicesDirectory(): string
    {
        return $this->fileHelper->getDataSubdirectory('invoices');
    }

    public function getInvoiceFile(Invoice $invoice): ?\SplFileInfo
    {
        $invoiceDirectory = $this->getInvoicesDirectory();
        $filename = $invoice->getInvoiceFilename();
        $full = $invoiceDirectory . $filename;

        if (is_file($full) && is_readable($full)) {
            return new \SplFileInfo($full);
        }

        return null;
    }

    public function saveGeneratedInvoice(InvoicePostRenderEvent $event): string
    {
        $invoiceDirectory = $this->getInvoicesDirectory();
        $filename = (string) new InvoiceFilename($event->getModel());

        $response = $event->getResponse();

        if ($event->getResponse()->headers->has('Content-Disposition')) {
            $disposition = $event->getResponse()->headers->get('Content-Disposition');
            $parts = explode(';', $disposition);
            foreach ($parts as $part) {
                if (stripos($part, 'filename=') === false) {
                    continue;
                }
                $filename = explode('filename=', $part);
                if (count($filename) > 1) {
                    $filename = $filename[1];
                }
            }
        } else {
            $disposition = $event->getResponse()->headers->get('Content-Type');
            $parts = explode(';', $disposition);
            $parts = explode('/', $parts[0]);
            $filename .= '.' . $parts[1];
        }

        if (is_file($invoiceDirectory . $filename)) {
            throw new \Exception(sprintf('Invoice "%s" already exists', $filename));
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            $file->move($invoiceDirectory, $filename);
        } else {
            $this->fileHelper->saveFile($invoiceDirectory . $filename, $event->getResponse()->getContent());
        }

        return $filename;
    }
}
