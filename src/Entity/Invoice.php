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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_invoices")
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

    // all fields are defined in the trait, as it is used for the "InvoiceTemplate" entity as well
    use InvoiceSettingsTrait;

    /**
     * @var bool
     */
    private $localized = false;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

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

    public function getCreatedAt(): ?\DateTime
    {
        if (!$this->localized) {
            if (null !== $this->createdAt && null !== $this->timezone) {
                $this->createdAt->setTimeZone(new DateTimeZone($this->timezone));
            }

            $this->localized = true;
        }

        return $this->createdAt;
    }

    public function getDueDate(): ?\DateTime
    {
        $tz = $this->timezone;
        if (null === $tz) {
            $tz = date_default_timezone_get();
        }

        return new \DateTime('+ ' . $this->dueDays . 'days', new DateTimeZone($tz));
    }

    public function isOverdue(): bool
    {
        $tz = $this->timezone;
        if (null === $tz) {
            $tz = date_default_timezone_get();
        }

        return $this->getDueDate()->getTimestamp() < (new \DateTime('now', $tz))->getTimestamp();
    }

    public function setFilename(string $filename): Invoice
    {
        $this->invoiceFilename = $filename;

        return $this;
    }

    public function setCreatedAt(\DateTime $createdAt): Invoice
    {
        $this->createdAt = $createdAt;
        $this->timezone = $createdAt->getTimezone()->getName();

        return $this;
    }

    public function setModel(InvoiceModel $model): Invoice
    {
        $this->customer = $model->getCustomer();

        $template = $model->getTemplate();
        $this->title = $template->getTitle();
        $this->company = $template->getCompany();
        $this->user = $model->getUser();
        $this->vatId = $template->getVatId();
        $this->address = $template->getAddress();
        $this->contact = $template->getContact();
        $this->dueDays = $template->getDueDays();
        $this->vat = $template->getVat();
        $this->calculator = $template->getCalculator();
        $this->numberGenerator = $template->getNumberGenerator();
        $this->renderer = $template->getRenderer();
        $this->paymentTerms = $template->getPaymentTerms();
        $this->paymentDetails = $template->getPaymentDetails();
        $this->decimalDuration = $template->isDecimalDuration();
        $this->language = $template->getLanguage();

        return $this;
    }

    public function setIsPaid(): Invoice
    {
        $this->status = self::STATUS_PAID;

        return $this;
    }

    public function setIsPending(): Invoice
    {
        $this->status = self::STATUS_PENDING;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getId();
    }

    // ---- trait methods below ---

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getNumberGenerator(): string
    {
        return $this->numberGenerator;
    }

    public function getDueDays(): int
    {
        return $this->dueDays;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function getCalculator(): string
    {
        return $this->calculator;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function getPaymentDetails(): ?string
    {
        return $this->paymentDetails;
    }

    public function isDecimalDuration(): bool
    {
        return $this->decimalDuration;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
