<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceTemplateRepository")
 * @ORM\Table(name="kimai2_invoice_templates",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"})
 *      }
 * )
 */
class InvoiceTemplate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=60, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=1, max=60)
     */
    private $name;

    // all fields are defined in the trait, as it is used for the "Invoice" entity as well
    use InvoiceSettingsTrait;

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

    public function setTitle(string $title): InvoiceTemplate
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

    public function getDueDays(): int
    {
        return $this->dueDays;
    }

    public function setDueDays(int $dueDays): InvoiceTemplate
    {
        $this->dueDays = $dueDays;

        return $this;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function setVat(float $vat): InvoiceTemplate
    {
        $this->vat = $vat;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): InvoiceTemplate
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

    public function isDecimalDuration(): bool
    {
        return $this->decimalDuration;
    }

    public function setDecimalDuration(bool $decimalDuration): InvoiceTemplate
    {
        $this->decimalDuration = $decimalDuration;

        return $this;
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
