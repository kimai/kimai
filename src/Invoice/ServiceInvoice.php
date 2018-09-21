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
 * A service to manage invoice dependencies.
 */
class ServiceInvoice
{
    /**
     * @var CalculatorInterface[]
     */
    protected $calculator = [];

    /**
     * @var RendererInterface[]
     */
    protected $renderer = [];

    /**
     * @var NumberGeneratorInterface[]
     */
    protected $numberGenerator = [];

    /**
     * @var InvoiceDocumentRepository
     */
    protected $documents;

    /**
     * @param InvoiceDocumentRepository $repository
     */
    public function __construct(InvoiceDocumentRepository $repository)
    {
        $this->documents = $repository;
    }

    /**
     * @param NumberGeneratorInterface $generator
     * @return $this
     */
    public function addNumberGenerator(NumberGeneratorInterface $generator)
    {
        $this->numberGenerator[] = $generator;

        return $this;
    }

    /**
     * @return NumberGeneratorInterface[]
     */
    public function getNumberGenerator()
    {
        return $this->numberGenerator;
    }

    /**
     * @param string $name
     * @return NumberGeneratorInterface|null
     */
    public function getNumberGeneratorByName(string $name)
    {
        foreach ($this->getNumberGenerator() as $generator) {
            if ($generator->getId() === $name) {
                return $generator;
            }
        }

        return null;
    }

    /**
     * @param CalculatorInterface $calculator
     * @return $this
     */
    public function addCalculator(CalculatorInterface $calculator)
    {
        $this->calculator[] = $calculator;

        return $this;
    }

    /**
     * @return CalculatorInterface[]
     */
    public function getCalculator()
    {
        return $this->calculator;
    }

    /**
     * @param string $name
     * @return CalculatorInterface|null
     */
    public function getCalculatorByName(string $name)
    {
        foreach ($this->getCalculator() as $calculator) {
            if ($calculator->getId() === $name) {
                return $calculator;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return InvoiceDocument|null
     */
    public function getDocumentByName(string $name)
    {
        return $this->documents->findByName($name);
    }

    /**
     * Returns an array of invoice renderer, which will consist of a unique name and a controller action.
     *
     * @return InvoiceDocument[]
     */
    public function getDocuments()
    {
        return $this->documents->findAll();
    }

    /**
     * @param RendererInterface $renderer
     * @return $this
     */
    public function addRenderer(RendererInterface $renderer)
    {
        $this->renderer[] = $renderer;

        return $this;
    }

    /**
     * Returns an array of invoice renderer.
     *
     * @return RendererInterface[]
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
}
