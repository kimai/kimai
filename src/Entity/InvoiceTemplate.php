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
 * InvoiceTemplate
 *
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceTemplateRepository")
 * @ORM\Table(
 *      name="invoice_templates",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"})
 *      }
 * )
 */
class InvoiceTemplate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", length=65535, nullable=true)
     */
    private $address;

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
     * @ORM\Column(name="vat", type="integer", length=2, nullable=true)
     * @Assert\Range(min = 0, max = 99)
     */
    private $vat = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="calculator", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     */
    private $calculator = 'default';
    /**
     * @var string
     *
     * @ORM\Column(name="number_generator", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     */
    private $numberGenerator = 'default';

    /**
     * @var string
     *
     * @ORM\Column(name="renderer", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     */
    private $renderer = 'default';

    /**
     * @var string
     *
     * @ORM\Column(name="payment_terms", type="text", length=65535, nullable=true)
     */
    private $paymentTerms;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return InvoiceTemplate
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return InvoiceTemplate
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumberGenerator(): ?string
    {
        return $this->numberGenerator;
    }

    /**
     * @param string $numberGenerator
     * @return InvoiceTemplate
     */
    public function setNumberGenerator(string $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
        return $this;
    }

    /**
     * @return int
     */
    public function getDueDays(): ?int
    {
        return $this->dueDays;
    }

    /**
     * @param int $dueDays
     * @return InvoiceTemplate
     */
    public function setDueDays(int $dueDays)
    {
        $this->dueDays = $dueDays;
        return $this;
    }

    /**
     * @return float
     */
    public function getVat(): ?float
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     * @return InvoiceTemplate
     */
    public function setVat(float $vat)
    {
        $this->vat = $vat;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return InvoiceTemplate
     */
    public function setCompany(string $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return string
     */
    public function getRenderer(): string
    {
        return $this->renderer;
    }

    /**
     * @param string $renderer
     * @return InvoiceTemplate
     */
    public function setRenderer(string $renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * @return string
     */
    public function getCalculator(): string
    {
        return $this->calculator;
    }

    /**
     * @param string $calculator
     * @return InvoiceTemplate
     */
    public function setCalculator(string $calculator)
    {
        $this->calculator = $calculator;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    /**
     * @param string $paymentTerms
     * @return InvoiceTemplate
     */
    public function setPaymentTerms(string $paymentTerms)
    {
        $this->paymentTerms = $paymentTerms;
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
