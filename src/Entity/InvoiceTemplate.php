<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\InvoiceTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_invoice_templates')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: InvoiceTemplateRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
class InvoiceTemplate implements EntityWithMetaFields
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: Types::STRING, length: 60, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 60)]
    private ?string $name = null;
    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;
    #[ORM\Column(name: 'company', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $company = null;
    #[ORM\Column(name: 'vat_id', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $vatId = null;
    #[ORM\Column(name: 'address', type: Types::TEXT, nullable: true)]
    private ?string $address = null;
    #[ORM\Column(name: 'contact', type: Types::TEXT, nullable: true)]
    private ?string $contact = null;
    #[ORM\Column(name: 'due_days', type: Types::INTEGER, length: 3, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 999)]
    private ?int $dueDays = 30;
    #[ORM\Column(name: 'vat', type: Types::FLOAT, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0.0, max: 99.99)]
    private ?float $vat = 0.00;
    #[ORM\Column(name: 'calculator', type: Types::STRING, length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $calculator = 'default';
    #[ORM\Column(name: 'number_generator', type: Types::STRING, length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $numberGenerator = 'default';
    #[ORM\Column(name: 'renderer', type: Types::STRING, length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $renderer = 'default';
    #[ORM\Column(name: 'payment_terms', type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;
    #[ORM\Column(name: 'payment_details', type: Types::TEXT, nullable: true)]
    private ?string $paymentDetails = null;
    /**
     * Used for translations and formatting money, numbers, dates and time.
     */
    #[ORM\Column(name: 'language', type: Types::STRING, length: 6, nullable: false)]
    #[Assert\NotBlank]
    private ?string $language = 'en';
    /**
     * Customer for this invoice template
     */
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull]
    private ?Customer $customer = null;
    /**
     * @var Collection<int, InvoiceTemplateMeta>
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: InvoiceTemplateMeta::class, cascade: ['persist'])]
    private Collection $meta;
    /**
     * @var array<Tax>
     */
    private array $taxRates = [];

    public function __construct()
    {
        $this->meta = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    // ---- trait methods below ---

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getAddress(): ?string
    {
        return $this->customer?->getFormattedAddress() ?? $this->address;
    }

    /**
     * @deprecated since 2.41
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getNumberGenerator(): string
    {
        return $this->numberGenerator;
    }

    public function setNumberGenerator(string $numberGenerator): void
    {
        $this->numberGenerator = $numberGenerator;
    }

    public function getDueDays(): ?int
    {
        return $this->dueDays;
    }

    public function setDueDays(?int $dueDays): void
    {
        $this->dueDays = $dueDays;
    }

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setVat(?float $vat): void
    {
        $this->vat = $vat;
    }

    public function getCompany(): ?string
    {
        return $this->customer?->getCompany() ?? $this->customer?->getName() ?? $this->company;
    }

    /**
     * @deprecated since 2.41
     */
    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function setRenderer(string $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function getCalculator(): string
    {
        return $this->calculator;
    }

    public function setCalculator(string $calculator): void
    {
        $this->calculator = $calculator;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): void
    {
        $this->paymentTerms = $paymentTerms;
    }

    public function getVatId(): ?string
    {
        return $this->customer?->getVatId() ?? $this->vatId;
    }

    /**
     * @deprecated since 2.41
     */
    public function setVatId(?string $vatId): void
    {
        $this->vatId = $vatId;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): void
    {
        $this->contact = $contact;
    }

    public function getPaymentDetails(): ?string
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(?string $paymentDetails): void
    {
        $this->paymentDetails = $paymentDetails;
    }

    /**
     * @deprecated since 2.0
     */
    public function isDecimalDuration(): bool
    {
        return true;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * @param array<Tax> $taxRates
     */
    public function setTaxRates(array $taxRates): void
    {
        $this->taxRates = $taxRates;
    }

    /**
     * @return Tax[]
     */
    public function getTaxRates(): array
    {
        if (\count($this->taxRates) > 0) {
            return $this->taxRates;
        }

        $tax = new Tax(
            TaxType::STANDARD,
            $this->vat ?? 0.00,
            'vat',
            true,
            null
        );

        return [$tax];
    }

    /**
     * @return Collection<int, InvoiceTemplateMeta>
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
            if ($field->getName() !== null && strtolower($field->getName()) === strtolower($name)) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        if ($meta->getName() === null) {
            throw new \InvalidArgumentException('Meta-field needs to have a name');
        }

        if (!$meta instanceof InvoiceTemplateMeta) {
            throw new \InvalidArgumentException('Meta-field needs to be an instanceof InvoiceTemplateMeta');
        }

        if (null === ($current = $this->getMetaField($meta->getName()))) {
            $meta->setEntity($this);
            $this->meta->add($meta);

            return $this;
        }

        $current->merge($meta);

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
