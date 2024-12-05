<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use App\Invoice\InvoiceModel;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_invoices')]
#[ORM\UniqueConstraint(columns: ['invoice_number'])]
#[ORM\UniqueConstraint(columns: ['invoice_filename'])]
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('invoiceNumber')]
#[UniqueEntity('invoiceFilename')]
#[Serializer\ExclusionPolicy('all')]
#[Exporter\Order(['id', 'createdAt', 'invoiceNumber', 'status', 'customer', 'subtotal', 'total', 'tax', 'currency', 'vat', 'dueDays', 'dueDate', 'paymentDate', 'user', 'invoiceFilename', 'customerNumber', 'comment'])]
#[Exporter\Expose(name: 'customer', label: 'customer', exp: 'object.getCustomer() === null ? null : object.getCustomer().getName()')]
#[Exporter\Expose(name: 'customerNumber', label: 'number', exp: 'object.getCustomer() === null ? null : object.getCustomer().getNumber()')]
#[Exporter\Expose(name: 'dueDate', label: 'invoice.due_days', type: 'datetime', exp: 'object.getDueDate() === null ? null : object.getDueDate()')]
#[Exporter\Expose(name: 'user', label: 'username', type: 'string', exp: 'object.getUser() === null ? null : object.getUser().getDisplayName()')]
#[Exporter\Expose(name: 'paymentDate', label: 'invoice.payment_date', type: 'date', exp: 'object.getPaymentDate() === null ? null : object.getPaymentDate()')]
class Invoice implements EntityWithMetaFields
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_NEW = 'new';

    /**
     * Unique invoice ID
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(name: 'invoice_number', type: 'string', length: 50, nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'invoice.number', type: 'string')]
    private ?string $invoiceNumber = null;
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Invoice'])]
    #[Exporter\Expose(label: 'comment')]
    private ?string $comment = null;
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[OA\Property(ref: '#/components/schemas/Customer')]
    private ?Customer $customer = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[OA\Property(ref: '#/components/schemas/User')]
    private ?User $user = null;
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'date', type: 'datetime')]
    private ?\DateTime $createdAt = null;
    #[ORM\Column(name: 'timezone', type: 'string', length: 64, nullable: false)]
    private ?string $timezone = null;
    #[ORM\Column(name: 'total', type: 'float', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'total_rate', type: 'float')]
    private float $total = 0.00;
    #[ORM\Column(name: 'tax', type: 'float', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'invoice.tax', type: 'float')]
    private float $tax = 0.00;
    #[ORM\Column(name: 'currency', type: 'string', length: 3, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(max: 3)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'currency', type: 'string')]
    private ?string $currency = null;
    #[ORM\Column(name: 'due_days', type: 'integer', length: 3, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 999)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Invoice'])]
    #[Exporter\Expose(label: 'due_days', type: 'integer')]
    private int $dueDays = 30;
    #[ORM\Column(name: 'vat', type: 'float', nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0.0, max: 99.99)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'tax_rate', type: 'float')]
    private float $vat = 0.00;
    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'status', type: 'string')]
    private string $status = self::STATUS_NEW;
    #[ORM\Column(name: 'invoice_filename', type: 'string', length: 150, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 150)]
    #[Exporter\Expose(label: 'file', type: 'string')]
    private ?string $invoiceFilename = null;
    private bool $localized = false;
    #[ORM\Column(name: 'payment_date', type: 'date', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?\DateTime $paymentDate = null;
    /**
     * Meta fields registered with the invoice
     *
     * @var Collection<InvoiceMeta>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Invoice'])]
    #[Serializer\Type(name: 'array<App\Entity\InvoiceMeta>')]
    #[Serializer\SerializedName('metaFields')]
    #[Serializer\Accessor(getter: 'getVisibleMetaFields')]
    private Collection $meta;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
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
                $this->createdAt->setTimezone(new \DateTimeZone($this->timezone));
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
        $template = $model->getTemplate();
        if ($template === null) {
            throw new \InvalidArgumentException('Missing invoice template');
        }

        if ($template->getDueDays() === null || $template->getVat() === null) {
            throw new \InvalidArgumentException('Missing due-days or vat setting');
        }

        $customer = $model->getCustomer();
        if ($customer === null) {
            throw new \InvalidArgumentException('Missing invoice customer');
        }

        $user = $model->getUser();
        if ($user === null) {
            throw new \InvalidArgumentException('Missing invoice user');
        }

        $this->setCustomer($customer);
        $this->setUser($user);
        $this->setTotal($model->getCalculator()->getTotal());
        $this->setTax($model->getCalculator()->getTax());
        $this->setInvoiceNumber($model->getInvoiceNumber());
        $this->setCurrency($model->getCurrency());
        $this->setCreatedAt($model->getInvoiceDate());
        $this->setDueDays($template->getDueDays());
        $this->setVat($template->getVat());

        return $this;
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function setIsNew(): Invoice
    {
        $this->setPaymentDate(null);
        $this->status = self::STATUS_NEW;

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setIsPending(): Invoice
    {
        $this->setPaymentDate(null);
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

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!\in_array($status, [self::STATUS_NEW, self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_CANCELED])) {
            throw new \InvalidArgumentException('Unknown invoice status');
        }

        $this->status = $status;
    }

    public function setIsCanceled(): void
    {
        $this->status = self::STATUS_CANCELED;
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

    #[Exporter\Expose(label: 'invoice.subtotal', type: 'float', name: 'subtotal')]
    public function getSubtotal(): float
    {
        return $this->total - $this->tax;
    }

    public function getPaymentDate(): ?\DateTime
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?\DateTime $paymentDate): Invoice
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getVisibleMetaFields(): array
    {
        $all = [];
        foreach ($this->meta as $meta) {
            if ($meta->isVisible()) {
                $all[] = $meta;
            }
        }

        return $all;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if (strtolower($field->getName()) === strtolower($name)) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        if (null === ($current = $this->getMetaField($meta->getName()))) {
            $meta->setEntity($this);
            $this->meta->add($meta);

            return $this;
        }

        $current->merge($meta);

        return $this;
    }

    public function setVat(float $vat): void
    {
        $this->vat = $vat;
    }

    public function setInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = \DateTime::createFromInterface($createdAt);
        $this->timezone = $createdAt->getTimezone()->getName();
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function setTax(float $tax): void
    {
        $this->tax = $tax;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function setDueDays(int $dueDays): void
    {
        $this->dueDays = $dueDays;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }

        $currentMeta = $this->meta;
        $this->meta = new ArrayCollection();
        /** @var InvoiceMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }
    }
}
