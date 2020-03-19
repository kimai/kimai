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
use App\Entity\User;
use App\Invoice\Hydrator\InvoiceItemDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelActivityHydrator;
use App\Invoice\Hydrator\InvoiceModelCustomerHydrator;
use App\Invoice\Hydrator\InvoiceModelDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelProjectHydrator;
use App\Invoice\Hydrator\InvoiceModelUserHydrator;
use App\Repository\Query\InvoiceQuery;

/**
 * InvoiceModel is the ONLY value that a RendererInterface receives for generating the invoice,
 * besides the InvoiceDocument which is used as a "template".
 */
final class InvoiceModel
{
    /**
     * @var Customer|null
     */
    private $customer;
    /**
     * @var InvoiceQuery
     */
    private $query;
    /**
     * @var InvoiceItemInterface[]
     */
    private $entries = [];
    /**
     * @var InvoiceTemplate
     */
    private $template;
    /**
     * @var CalculatorInterface
     */
    private $calculator;
    /**
     * @var NumberGeneratorInterface
     */
    private $generator;
    /**
     * @var \DateTime
     */
    private $invoiceDate;
    /**
     * @var User
     */
    private $user;
    /**
     * @var InvoiceFormatter
     */
    private $formatter;
    /**
     * @var InvoiceModelHydrator[]
     */
    private $modelHydrator = [];
    /**
     * @var InvoiceItemHydrator[]
     */
    private $itemHydrator = [];
    /**
     * @var string
     */
    private $invoiceNumber;

    public function __construct(InvoiceFormatter $formatter)
    {
        $this->invoiceDate = new \DateTime();
        $this->formatter = $formatter;
        $this->addModelHydrator(new InvoiceModelDefaultHydrator());
        $this->addModelHydrator(new InvoiceModelCustomerHydrator());
        $this->addModelHydrator(new InvoiceModelProjectHydrator());
        $this->addModelHydrator(new InvoiceModelActivityHydrator());
        $this->addModelHydrator(new InvoiceModelUserHydrator());
        $this->addItemHydrator(new InvoiceItemDefaultHydrator());
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
     * Returns the raw data from the model.
     *
     * Do not use this method for rendering the invoice, use getItems() instead.
     *
     * @return InvoiceItemInterface[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @deprecated since 1.3 - will be removed with 2.0
     * @param InvoiceItemInterface[] $entries
     * @return InvoiceModel
     */
    public function setEntries(array $entries): InvoiceModel
    {
        @trigger_error('setEntries() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->entries = $entries;

        return $this;
    }

    /**
     * @param InvoiceItemInterface[] $entries
     * @return InvoiceModel
     */
    public function addEntries(array $entries): InvoiceModel
    {
        $this->entries = array_merge($this->entries, $entries);

        return $this;
    }

    public function addModelHydrator(InvoiceModelHydrator $hydrator): InvoiceModel
    {
        $this->modelHydrator[] = $hydrator;

        return $this;
    }

    public function addItemHydrator(InvoiceItemHydrator $hydrator): InvoiceModel
    {
        $hydrator->setInvoiceModel($this);

        $this->itemHydrator[] = $hydrator;

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
     * @param Customer|null $customer
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

    public function getInvoiceDate(): \DateTime
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTime $date): InvoiceModel
    {
        $this->invoiceDate = $date;

        return $this;
    }

    public function getInvoiceNumber(): string
    {
        if (null === $this->generator) {
            throw new \Exception('InvoiceModel::getInvoiceNumber() cannot be called before calling setNumberGenerator()');
        }

        if (null === $this->invoiceNumber) {
            $this->invoiceNumber = $this->generator->getInvoiceNumber();
        }

        return $this->invoiceNumber;
    }

    public function setNumberGenerator(NumberGeneratorInterface $generator): InvoiceModel
    {
        $this->generator = $generator;
        $this->generator->setModel($this);

        return $this;
    }

    /**
     * @deprecated since 1.9 - will be removed with 2.0 - use getInvoiceNumber() instead
     */
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

    /**
     * Returns the user who is currently creating the invoice.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): InvoiceModel
    {
        $this->user = $user;

        return $this;
    }

    public function getFormatter(): ?InvoiceFormatter
    {
        return $this->formatter;
    }

    public function getCurrency(): string
    {
        if (null === $this->getCustomer()) {
            // this should be set from the configuration
            return Customer::DEFAULT_CURRENCY;
        }

        return $this->getCustomer()->getCurrency();
    }

    public function toArray(): array
    {
        $values = [];

        foreach ($this->modelHydrator as $hydrator) {
            $values = array_merge($values, $hydrator->hydrate($this));
        }

        return $values;
    }

    public function itemToArray(InvoiceItem $invoiceItem): array
    {
        $values = [];

        foreach ($this->itemHydrator as $hydrator) {
            $values = array_merge($values, $hydrator->hydrate($invoiceItem));
        }

        return $values;
    }
}
