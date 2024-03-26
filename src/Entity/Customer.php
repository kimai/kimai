<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_customers')]
#[ORM\Index(columns: ['visible'])]
#[ORM\Entity(repositoryClass: 'App\Repository\CustomerRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
#[Exporter\Order(['id', 'name', 'company', 'number', 'vatId', 'address', 'contact', 'email', 'phone', 'mobile', 'fax', 'homepage', 'country', 'currency', 'timezone', 'budget', 'timeBudget', 'budgetType', 'color', 'visible', 'teams', 'comment', 'billable'])]
#[Constraints\Customer]
class Customer implements EntityWithMetaFields, EntityWithBudget
{
    public const DEFAULT_CURRENCY = 'EUR';

    use BudgetTrait;
    use ColorTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: 'string', length: 150, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'name')]
    private ?string $name = null;
    #[ORM\Column(name: 'number', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'number')]
    private ?string $number = null;
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'comment')]
    private ?string $comment = null;
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'visible', type: 'boolean')]
    private bool $visible = true;
    #[ORM\Column(name: 'billable', type: 'boolean', nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'billable', type: 'boolean')]
    private bool $billable = true;
    #[ORM\Column(name: 'company', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'company')]
    private ?string $company = null;
    #[ORM\Column(name: 'vat_id', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'vat_id')]
    private ?string $vatId = null;
    #[ORM\Column(name: 'contact', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'contact')]
    private ?string $contact = null;
    #[ORM\Column(name: 'address', type: 'text', nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'address')]
    private ?string $address = null;
    #[ORM\Column(name: 'country', type: 'string', length: 2, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Country]
    #[Assert\Length(max: 2)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'country')]
    private ?string $country = null;
    #[ORM\Column(name: 'currency', type: 'string', length: 3, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Currency]
    #[Assert\Length(max: 3)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer'])]
    #[Exporter\Expose(label: 'currency')]
    private ?string $currency = self::DEFAULT_CURRENCY;
    #[ORM\Column(name: 'phone', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'phone')]
    private ?string $phone = null;
    #[ORM\Column(name: 'fax', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'fax')]
    private ?string $fax = null;
    #[ORM\Column(name: 'mobile', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'mobile')]
    private ?string $mobile = null;
    /**
     * Customers contact email
     */
    #[ORM\Column(name: 'email', type: 'string', length: 75, nullable: true)]
    #[Assert\Length(max: 75)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'email')]
    private ?string $email = null;
    #[ORM\Column(name: 'homepage', type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'homepage')]
    private ?string $homepage = null;
    /**
     * Timezone of begin and end
     */
    #[ORM\Column(name: 'timezone', type: 'string', length: 64, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Timezone]
    #[Assert\Length(max: 64)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'timezone')]
    private ?string $timezone = null;
    /**
     * Meta fields registered with the customer
     *
     * @var Collection<CustomerMeta>
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer'])]
    #[Serializer\Type(name: 'array<App\Entity\CustomerMeta>')]
    #[Serializer\SerializedName('metaFields')]
    #[Serializer\Accessor(getter: 'getVisibleMetaFields')]
    private Collection $meta;
    /**
     * Teams with access to the customer
     *
     * @var Collection<Team>
     */
    #[ORM\JoinTable(name: 'kimai2_customers_teams')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'team_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'customers', cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Team'))]
    private Collection $teams;
    /**
     * Default invoice template for this customer
     */
    #[ORM\ManyToOne(targetEntity: InvoiceTemplate::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
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

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
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

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function setVatId(?string $vatId): void
    {
        $this->vatId = $vatId;
    }

    public function setContact(?string $contact): void
    {
        $this->contact = $contact;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setFax(?string $fax): void
    {
        $this->fax = $fax;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setEmail(?string $mail): void
    {
        $this->email = $mail;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setHomepage(?string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
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

    public function addTeam(Team $team): void
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams->add($team);
        $team->addCustomer($this);
    }

    public function removeTeam(Team $team): void
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
        if ($this->id !== null) {
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
