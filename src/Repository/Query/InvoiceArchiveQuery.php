<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Form\Model\DateRange;

/**
 * Query for created invoices.
 */
class InvoiceArchiveQuery extends BaseQuery implements DateRangeInterface
{
    use DateRangeTrait;

    public const INVOICE_ARCHIVE_ORDER_ALLOWED = [
        'date', 'invoice.number', 'status', 'total_rate'
        // TODO other fields have a problem with translation
        // , 'tax', 'payed'
    ];

    /**
     * Filter for invoice status (by default all)
     * @var string[]
     */
    private array $status = [];
    /**
     * @var Customer[]
     */
    private array $customers = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'date',
            'order' => self::ORDER_DESC,
            'dateRange' => new DateRange(),
            'customers' => [],
            'status' => [],
        ]);
    }

    public function addCustomer(Customer $customer): void
    {
        $this->customers[] = $customer;
    }

    public function setCustomers(array $customers): void
    {
        foreach ($customers as $customer) {
            $this->addCustomer($customer);
        }
    }

    /**
     * @return Customer[]
     */
    public function getCustomers(): array
    {
        return $this->customers;
    }

    public function hasCustomers(): bool
    {
        return !empty($this->customers);
    }

    public function hasStatus(): bool
    {
        return !empty($this->status);
    }

    public function getStatus(): array
    {
        return $this->status;
    }

    /**
     * @param string[] $status
     */
    public function setStatus(array $status): void
    {
        foreach ($status as $s) {
            $this->addStatus($s);
        }
    }

    public function addStatus(string $status): void
    {
        if (!\in_array($status, [Invoice::STATUS_NEW, Invoice::STATUS_PENDING, Invoice::STATUS_PAID, Invoice::STATUS_CANCELED])) {
            throw new \InvalidArgumentException('Unknown invoice status given.');
        }

        if (!\in_array($status, $this->status)) {
            $this->status[] = $status;
        }
    }
}
