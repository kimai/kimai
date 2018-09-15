<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Invoice\CalculatorInterface;
use App\Invoice\NumberGeneratorInterface;
use App\Repository\Query\InvoiceQuery;

/**
 * InvoiceModel is the ONLY value that a RendererInterface receives for generating the invoice,
 * besides the InvoiceDocument which is used as a "template".
 */
class InvoiceModel
{
    /**
     * @var Customer
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
    public function getQuery(): InvoiceQuery
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
    public function setEntries(array $entries)
    {
        $this->entries = $entries;

        return $this;
    }

    /**
     * @return InvoiceTemplate
     */
    public function getTemplate(): ?InvoiceTemplate
    {
        return $this->template;
    }

    /**
     * @param InvoiceTemplate $template
     * @return InvoiceModel
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return InvoiceModel
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDueDate(): \DateTime
    {
        return new \DateTime('+' . $this->getTemplate()->getDueDays() . ' days');
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate(): \DateTime
    {
        return $this->invoiceDate;
    }

    /**
     * @param NumberGeneratorInterface $generator
     * @return InvoiceModel
     */
    public function setNumberGenerator(NumberGeneratorInterface $generator)
    {
        $this->generator = $generator;
        $this->generator->setModel($this);

        return $this;
    }

    /**
     * @return NumberGeneratorInterface
     */
    public function getNumberGenerator(): NumberGeneratorInterface
    {
        return $this->generator;
    }

    /**
     * @param CalculatorInterface $calculator
     * @return InvoiceModel
     */
    public function setCalculator(CalculatorInterface $calculator)
    {
        $this->calculator = $calculator;
        $this->calculator->setModel($this);

        return $this;
    }

    /**
     * @return CalculatorInterface
     */
    public function getCalculator(): CalculatorInterface
    {
        return $this->calculator;
    }
}
