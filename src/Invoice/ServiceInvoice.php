<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\InvoiceDocument;
use App\Repository\InvoiceDocumentRepository;

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

    public function __construct(InvoiceDocumentRepository $repository)
    {
        $this->documents = $repository;
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
}
