<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Repository\Query\InvoiceQuery;

/**
 * InvoiceModel is the ONLY value that a RendererInterface receives for generating the invoice,
 * besides the InvoiceDocument which is used as a "template".
 */
class InvoiceModel
{
    /**
     * @var Customer|null
     */
    protected $customer;

    /**
     * @var InvoiceQuery
     */
    protected $query;

    /**
     * @var Timesheet[]
     */
    protected $entries = [];

    /**
     * @var InvoiceTemplate
     */
    protected $template;

    /**
     * @var CalculatorInterface
     */
    protected $calculator;

    /**
     * @var NumberGeneratorInterface
     */
    protected $generator;

    /**
     * @var \DateTime
     */
    protected $invoiceDate;

    public function __construct()
    {
        $this->invoiceDate = new \DateTime();
    }

    /**
     * @return InvoiceQuery
     */
    public function getQuery(): ?InvoiceQuery
    {
        return $this->query;
    }

    /**
     * @param InvoiceQuery $query
     * @return InvoiceModel
     */
    public function setQuery(InvoiceQuery $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Do not use this method for rendering the invoice, use InvoiceModel::getCalculator()->getEntries() instead.
     *
     * @return Timesheet[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param Timesheet[] $entries
     * @return InvoiceModel
     */
    public function setEntries(array $entries): InvoiceModel
    {
        $this->entries = $entries;

        return $this;
    }

    public function getTemplate(): ?InvoiceTemplate
    {
        return $this->template;
    }

    public function setTemplate(InvoiceTemplate $template): InvoiceModel
    {
        $this->template = $template;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return InvoiceModel
     */
    public function setCustomer($customer): InvoiceModel
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        if (null === $this->getTemplate()) {
            return null;
        }

        return new \DateTime('+' . $this->getTemplate()->getDueDays() . ' days');
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate(): \DateTime
    {
        return $this->invoiceDate;
    }

    public function setNumberGenerator(NumberGeneratorInterface $generator): InvoiceModel
    {
        $this->generator = $generator;
        $this->generator->setModel($this);

        return $this;
    }

    public function getNumberGenerator(): ?NumberGeneratorInterface
    {
        return $this->generator;
    }

    public function setCalculator(CalculatorInterface $calculator): InvoiceModel
    {
        $this->calculator = $calculator;
        $this->calculator->setModel($this);

        return $this;
    }

    public function getCalculator(): ?CalculatorInterface
    {
        return $this->calculator;
    }
}
