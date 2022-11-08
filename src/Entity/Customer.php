<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Serializer\ExclusionPolicy("all")
 *
 * @Exporter\Order({"id", "name", "company", "number", "vatId", "address", "contact","email", "phone", "mobile", "fax", "homepage", "country", "currency", "timezone", "budget", "timeBudget", "budgetType", "color", "visible", "teams", "comment", "billable"})
 * @ Exporter\Expose("teams", label="team", exp="object.getTeams().toArray()", type="array")
 */
#[ORM\Table(name: 'kimai2_customers')]
#[ORM\Index(columns: ['visible'])]
#[ORM\Entity(repositoryClass: 'App\Repository\CustomerRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Customer implements EntityWithMetaFields, EntityWithBudget
{
    public const DEFAULT_CURRENCY = 'EUR';

    use BudgetTrait;
    use ColorTrait;

    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="id", type="integer")
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="name")
     */
    #[ORM\Column(name: 'name', type: 'string', length: 150, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 150)]
    private ?string $name;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="number")
     */
    #[ORM\Column(name: 'number', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $number = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="comment")
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private ?string $comment = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="visible", type="boolean")
     */
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    #[Assert\NotNull]
    private bool $visible = true;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Exporter\Expose(label="billable", type="boolean")
     */
    #[ORM\Column(name: 'billable', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    private bool $billable = true;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="company")
     */
    #[ORM\Column(name: 'company', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $company = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="vat_id")
     */
    #[ORM\Column(name: 'vat_id', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $vatId = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="contact")
     */
    #[ORM\Column(name: 'contact', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $contact = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="address")
     */
    #[ORM\Column(name: 'address', type: 'text', nullable: true)]
    private ?string $address = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="country")
     */
    #[ORM\Column(name: 'country', type: 'string', length: 2, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Country]
    #[Assert\Length(max: 2)]
    private ?string $country = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     * @Exporter\Expose(label="currency")
     */
    #[ORM\Column(name: 'currency', type: 'string', length: 3, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Currency]
    #[Assert\Length(max: 3)]
    private string $currency = self::DEFAULT_CURRENCY;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="phone")
     */
    #[ORM\Column(name: 'phone', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $phone = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="fax")
     */
    #[ORM\Column(name: 'fax', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $fax = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="mobile")
     */
    #[ORM\Column(name: 'mobile', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $mobile = null;
    /**
     * Customers contact email
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="email")
     */
    #[ORM\Column(name: 'email', type: 'string', length: 75, nullable: true)]
    #[Assert\Length(max: 75)]
    private ?string $email = null;
    /**
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     * @Exporter\Expose(label="homepage")
     */
    #[ORM\Column(name: 'homepage', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $homepage = null;
    /**
     * Timezone of begin and end
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="timezone")
     */
    #[ORM\Column(name: 'timezone', type: 'string', length: 64, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private ?string $timezone = null;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this customer
     *
     * @var Collection<CustomerMeta>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     * @Serializer\Type(name="array<App\Entity\CustomerMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     */
    #[ORM\OneToMany(targetEntity: 'App\Entity\CustomerMeta', mappedBy: 'customer', cascade: ['persist'])]
    private Collection $meta;
    /**
     * Teams
     *
     * If no team is assigned, everyone can access the customer
     *
     * @var Collection<Team>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     * @OA\Property(type="array", @OA\Items(ref="#/components/schemas/Team"))
     */
    #[ORM\JoinTable(name: 'kimai2_customers_teams')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'team_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: 'App\Entity\Team', cascade: ['persist', 'remove'], inversedBy: 'customers')]
    private Collection $teams;
    /**
     * Default invoice template for this customer
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\InvoiceTemplate')]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?InvoiceTemplate $invoiceTemplate = null;
    #[ORM\Column(name: 'invoice_text', type: 'text', nullable: true)]
    private ?string $invoiceText = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): Customer
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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setBillable(bool $billable): void
    {
        $this->billable = $billable;
    }

    public function isBillable(): bool
    {
        return $this->billable;
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

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function setVatId(?string $vatId): Customer
    {
        $this->vatId = $vatId;

        return $this;
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

    public function setCountry(?string $country): Customer
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

    public function hasInvoiceTemplate(): bool
    {
        return $this->invoiceTemplate !== null;
    }

    public function getInvoiceTemplate(): ?InvoiceTemplate
    {
        return $this->invoiceTemplate;
    }

    public function setInvoiceTemplate(?InvoiceTemplate $invoiceTemplate): void
    {
        $this->invoiceTemplate = $invoiceTemplate;
    }

    public function getInvoiceText(): ?string
    {
        return $this->invoiceText;
    }

    public function setInvoiceText(?string $invoiceText): void
    {
        $this->invoiceText = $invoiceText;
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

    /**
     * @param string $name
     * @return bool|int|string|null
     */
    public function getMetaFieldValue(string $name)
    {
        $field = $this->getMetaField($name);
        if ($field === null) {
            return null;
        }

        return $field->getValue();
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

    public function addTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams->add($team);
        $team->addCustomer($this);
    }

    public function removeTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            return;
        }
        $this->teams->removeElement($team);
        $team->removeCustomer($this);
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
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

        $currentTeams = $this->teams;
        $this->teams = new ArrayCollection();
        /** @var Team $team */
        foreach ($currentTeams as $team) {
            $this->addTeam($team);
        }

        $currentMeta = $this->meta;
        $this->meta = new ArrayCollection();
        /** @var CustomerMeta $meta */
        foreach ($currentMeta as $meta) {
            $newMeta = clone $meta;
            $newMeta->setEntity($this);
            $this->setMetaField($newMeta);
        }
    }
}
