<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Invoice\InvoiceModel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_invoices",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"invoice_number"}),
 *          @ORM\UniqueConstraint(columns={"invoice_filename"})
 *      }
 * )
 * @UniqueEntity("invoiceNumber")
 * @UniqueEntity("invoiceFilename")
 *
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 */
class Invoice
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_NEW = 'new';

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_number", type="string", length=50, nullable=false)
     * @Assert\NotNull()
     */
    private $invoiceNumber;

    /**
     * @var Customer|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $customer;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Assert\NotNull()
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=false)
     */
    private $timezone;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $total = 0.00;

    /**
     * @var float
     *
     * @ORM\Column(name="tax", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $tax = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @Assert\Length(max=3)
     */
    private $currency;

    /**
     * @var int
     *
     * @ORM\Column(name="due_days", type="integer", length=3, nullable=false)
     * @Assert\Range(min = 0, max = 999)
     */
    private $dueDays = 30;

    /**
     * @var float
     *
     * @ORM\Column(name="vat", type="float", nullable=false)
     * @Assert\Range(min = 0.0, max = 99.99)
     */
    private $vat = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status = self::STATUS_NEW;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_filename", type="string", length=100, nullable=false)
     */
    private $invoiceFilename;

    /**
     * @var bool
     */
    private $localized = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getCreatedAt(): ?\DateTime
    {
        if (!$this->localized) {
            if (null !== $this->createdAt && null !== $this->timezone) {
                $this->createdAt->setTimeZone(new \DateTimeZone($this->timezone));
            }

            $this->localized = true;
        }

        return $this->createdAt;
    }

    public function getDueDate(): ?\DateTime
    {
        if (null === $this->getCreatedAt()) {
            return null;
        }

        $dueDate = clone $this->getCreatedAt();
        $dueDate->modify('+ ' . $this->dueDays . 'days');

        return $dueDate;
    }

    public function isOverdue(): bool
    {
        if (null === $this->getDueDate()) {
            return false;
        }

        return $this->getDueDate()->getTimestamp() < (new \DateTime('now', new \DateTimeZone($this->timezone)))->getTimestamp();
    }

    public function setFilename(string $filename): Invoice
    {
        $this->invoiceFilename = $filename;

        return $this;
    }

    public function setModel(InvoiceModel $model): Invoice
    {
        $this->customer = $model->getCustomer();
        $this->user = $model->getUser();
        $this->total = $model->getCalculator()->getTotal();
        $this->tax = $model->getCalculator()->getTax();
        $this->invoiceNumber = $model->getInvoiceNumber();
        $this->currency = $model->getCurrency();

        $createdAt = $model->getInvoiceDate();
        $this->createdAt = $createdAt;
        $this->timezone = $createdAt->getTimezone()->getName();

        $template = $model->getTemplate();
        $this->dueDays = $template->getDueDays();
        $this->vat = $template->getVat();

        return $this;
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function setIsNew(): Invoice
    {
        $this->status = self::STATUS_NEW;

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setIsPending(): Invoice
    {
        $this->status = self::STATUS_PENDING;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function setIsPaid(): Invoice
    {
        $this->status = self::STATUS_PAID;

        return $this;
    }

    public function getDueDays(): int
    {
        return $this->dueDays;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getInvoiceFilename(): ?string
    {
        return $this->invoiceFilename;
    }
}
