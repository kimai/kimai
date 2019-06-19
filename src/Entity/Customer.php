<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_customers")
 * @ORM\Entity(repositoryClass="App\Repository\CustomerRepository")
 */
class Customer implements EntityWithMetaFields
{
    public const DEFAULT_CURRENCY = 'EUR';

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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=50, nullable=true)
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", length=65535, nullable=true)
     */
    private $comment;

    /**
     * @var Project[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Project", mappedBy="customer")
     */
    private $projects;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(name="contact", type="string", length=255, nullable=true)
     */
    private $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", length=65535, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=2, nullable=false)
     * @Assert\NotBlank()
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @Assert\NotBlank()
     */
    private $currency = self::DEFAULT_CURRENCY;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=255, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="homepage", type="string", length=255, nullable=true)
     */
    private $homepage;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $timezone;

    // keep the trait include exactly here, for placing the column at the correct position
    use RatesTrait;
    use ColorTrait;
    use BudgetTrait;

    /**
     * @var CustomerMeta[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerMeta", mappedBy="customer", cascade={"persist"})
     */
    private $meta;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->meta = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): Customer
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setNumber(?string $number): Customer
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setComment(?string $comment): Customer
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): Customer
    {
        $this->visible = $visible;

        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setCompany(?string $company): Customer
    {
        $this->company = $company;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setContact(?string $contact): Customer
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setAddress(?string $address): Customer
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCountry(string $country): Customer
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCurrency(string $currency): Customer
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setPhone(?string $phone): Customer
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setFax(?string $fax): Customer
    {
        $this->fax = $fax;

        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setMobile(?string $mobile): Customer
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setEmail(?string $mail): Customer
    {
        $this->email = $mail;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setHomepage(?string $homepage): Customer
    {
        $this->homepage = $homepage;

        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setTimezone(string $timezone): Customer
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @return Collection<Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    /**
     * @internal only here for symfony forms
     * @return Collection|MetaTableTypeInterface[]
     */
    public function getMetaFields(): Collection
    {
        return $this->meta;
    }

    public function getMetaField(string $name): ?MetaTableTypeInterface
    {
        foreach ($this->meta as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function setMetaField(MetaTableTypeInterface $meta): EntityWithMetaFields
    {
        $meta->setEntity($this);
        $this->meta->add($meta);

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
