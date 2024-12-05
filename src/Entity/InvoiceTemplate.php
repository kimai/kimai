<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\InvoiceTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_invoice_templates')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: InvoiceTemplateRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
class InvoiceTemplate
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: 'string', length: 60, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 60)]
    private ?string $name = null;
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank]
    private ?string $title = null;
    #[ORM\Column(name: 'company', type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank]
    private ?string $company = null;
    #[ORM\Column(name: 'vat_id', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $vatId = null;
    #[ORM\Column(name: 'address', type: 'text', nullable: true)]
    private ?string $address = null;
    #[ORM\Column(name: 'contact', type: 'text', nullable: true)]
    private ?string $contact = null;
    #[ORM\Column(name: 'due_days', type: 'integer', length: 3, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 999)]
    private ?int $dueDays = 30;
    #[ORM\Column(name: 'vat', type: 'float', nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0.0, max: 99.99)]
    private ?float $vat = 0.00;
    #[ORM\Column(name: 'calculator', type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $calculator = 'default';
    #[ORM\Column(name: 'number_generator', type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $numberGenerator = 'default';
    #[ORM\Column(name: 'renderer', type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $renderer = 'default';
    #[ORM\Column(name: 'payment_terms', type: 'text', nullable: true)]
    private ?string $paymentTerms = null;
    #[ORM\Column(name: 'payment_details', type: 'text', nullable: true)]
    private ?string $paymentDetails = null;
    /**
     * Used for translations and formatting money, numbers, dates and time.
     */
    #[ORM\Column(name: 'language', type: 'string', length: 6, nullable: false)]
    #[Assert\NotBlank]
    private ?string $language = 'en';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): InvoiceTemplate
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    // ---- trait methods below ---

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): InvoiceTemplate
    {
        $this->title = $title;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): InvoiceTemplate
    {
        $this->address = $address;

        return $this;
    }

    public function getNumberGenerator(): string
    {
        return $this->numberGenerator;
    }

    public function setNumberGenerator(string $numberGenerator): InvoiceTemplate
    {
        $this->numberGenerator = $numberGenerator;

        return $this;
    }

    public function getDueDays(): ?int
    {
        return $this->dueDays;
    }

    public function setDueDays(?int $dueDays): InvoiceTemplate
    {
        $this->dueDays = $dueDays;

        return $this;
    }

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setVat(?float $vat): InvoiceTemplate
    {
        $this->vat = $vat;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): InvoiceTemplate
    {
        $this->company = $company;

        return $this;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function setRenderer(string $renderer): InvoiceTemplate
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function getCalculator(): string
    {
        return $this->calculator;
    }

    public function setCalculator(string $calculator): InvoiceTemplate
    {
        $this->calculator = $calculator;

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): InvoiceTemplate
    {
        $this->paymentTerms = $paymentTerms;

        return $this;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function setVatId(?string $vatId): InvoiceTemplate
    {
        $this->vatId = $vatId;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): InvoiceTemplate
    {
        $this->contact = $contact;

        return $this;
    }

    public function getPaymentDetails(): ?string
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(?string $paymentDetails): InvoiceTemplate
    {
        $this->paymentDetails = $paymentDetails;

        return $this;
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

    public function setLanguage(?string $language): InvoiceTemplate
    {
        $this->language = $language;

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
