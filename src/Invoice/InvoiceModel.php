<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Activity\ActivityStatisticService;
use App\Customer\CustomerStatisticService;
use App\Entity\Customer;
use App\Entity\ExportableItem;
use App\Entity\InvoiceTemplate;
use App\Entity\User;
use App\Invoice\Hydrator\InvoiceItemDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelActivityHydrator;
use App\Invoice\Hydrator\InvoiceModelCustomerHydrator;
use App\Invoice\Hydrator\InvoiceModelDefaultHydrator;
use App\Invoice\Hydrator\InvoiceModelProjectHydrator;
use App\Invoice\Hydrator\InvoiceModelUserHydrator;
use App\Project\ProjectStatisticService;
use App\Repository\Query\InvoiceQuery;

/**
 * InvoiceModel is the ONLY value that a RendererInterface receives for generating the invoice,
 * besides the InvoiceDocument which is used as a "template".
 */
final class InvoiceModel
{
    private ?Customer $customer = null;
    private ?InvoiceQuery $query = null;
    /**
     * @var ExportableItem[]
     */
    private array $entries = [];
    private ?InvoiceTemplate $template = null;
    private ?CalculatorInterface $calculator = null;
    private ?NumberGeneratorInterface $generator = null;
    private \DateTimeInterface $invoiceDate;
    private ?User $user = null;
    private InvoiceFormatter $formatter;
    /**
     * @var InvoiceModelHydrator[]
     */
    private array $modelHydrator = [];
    /**
     * @var InvoiceItemHydrator[]
     */
    private array $itemHydrator = [];
    private ?string $invoiceNumber = null;
    private bool $hideZeroTax = false;

    /**
     * @internal use InvoiceModelFactory
     */
    public function __construct(InvoiceFormatter $formatter, CustomerStatisticService $customerStatistic, ProjectStatisticService $projectStatistic, ActivityStatisticService $activityStatistic)
    {
        $this->invoiceDate = new \DateTimeImmutable();
        $this->formatter = $formatter;
        $this->addModelHydrator(new InvoiceModelDefaultHydrator());
        $this->addModelHydrator(new InvoiceModelCustomerHydrator($customerStatistic));
        $this->addModelHydrator(new InvoiceModelProjectHydrator($projectStatistic));
        $this->addModelHydrator(new InvoiceModelActivityHydrator($activityStatistic));
        $this->addModelHydrator(new InvoiceModelUserHydrator());
        $this->addItemHydrator(new InvoiceItemDefaultHydrator());
    }

    public function getQuery(): ?InvoiceQuery
    {
        return $this->query;
    }

    public function setQuery(InvoiceQuery $query): void
    {
        $this->query = $query;
    }

    /**
     * Returns the raw data from the model.
     *
     * Do not use this method for rendering the invoice, use getCalculator()->getEntries() instead.
     *
     * @return ExportableItem[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param ExportableItem[] $entries
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

    public function setTemplate(InvoiceTemplate $template): void
    {
        $this->template = $template;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * Requires the template and invoice date to be set
     */
    public function getDueDate(): \DateTimeInterface
    {
        $date = \DateTimeImmutable::createFromInterface($this->getInvoiceDate());

        $dueDays = 14;
        if ($this->getTemplate() !== null) {
            $dueDays = $this->getTemplate()->getDueDays();
        }

        return $date->add(new \DateInterval('P' . $dueDays . 'D'));
    }

    public function getInvoiceDate(): \DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTimeInterface $date): void
    {
        $this->invoiceDate = $date;
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

    public function getFormatter(): InvoiceFormatter
    {
        return $this->formatter;
    }

    public function setFormatter(InvoiceFormatter $formatter): InvoiceModel
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function getCurrency(): string
    {
        if (null !== $this->getCustomer() && $this->getCustomer()->getCurrency() !== null) {
            return $this->getCustomer()->getCurrency();
        }

        return Customer::DEFAULT_CURRENCY;
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

    public function isHideZeroTax(): bool
    {
        return $this->hideZeroTax;
    }

    public function setHideZeroTax(bool $hideZeroTax): void
    {
        $this->hideZeroTax = $hideZeroTax;
    }
}
