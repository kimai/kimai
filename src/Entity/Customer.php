<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Doctrine\Behavior\CreatedAt;
use App\Doctrine\Behavior\CreatedTrait;
use App\Export\Annotation as Exporter;
use App\Repository\CustomerRepository;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_customers')]
#[ORM\Index(columns: ['visible'])]
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
#[Exporter\Order(['id', 'name', 'company', 'number', 'vatId', 'address', 'contact', 'email', 'phone', 'mobile', 'fax', 'homepage', 'addressLine1', 'addressLine2', 'addressLine3', 'postCode', 'city', 'country', 'currency', 'timezone', 'budget', 'timeBudget', 'budgetType', 'color', 'visible', 'comment', 'billable'])]
#[Constraints\Customer]
class Customer implements EntityWithMetaFields, EntityWithBudget, CreatedAt
{
    public const DEFAULT_CURRENCY = 'EUR';

    use BudgetTrait;
    use ColorTrait;
    use CreatedTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: Types::STRING, length: 150, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'name')]
    private ?string $name = null;
    #[ORM\Column(name: 'number', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'number')]
    private ?string $number = null;
    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'comment')]
    private ?string $comment = null;
    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false)]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'visible', type: 'boolean')]
    private bool $visible = true;
    #[ORM\Column(name: 'billable', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'billable', type: 'boolean')]
    private bool $billable = true;
    #[ORM\Column(name: 'company', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'company')]
    private ?string $company = null;
    #[ORM\Column(name: 'vat_id', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'vat_id')]
    private ?string $vatId = null;
    /**
     * Contact person (name)
     */
    #[ORM\Column(name: 'contact', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'contact')]
    private ?string $contact = null;
    /**
     * Unstructured address, better use the fields: addressLine1-3, postcode, city, country
     */
    #[ORM\Column(name: 'address', type: Types::TEXT, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'address')]
    private ?string $address = null;
    #[ORM\Column(name: 'country', type: Types::STRING, length: 2, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Country]
    #[Assert\Length(max: 2)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'country')]
    private ?string $country = null;
    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Currency]
    #[Assert\Length(max: 3)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'currency')]
    private ?string $currency = self::DEFAULT_CURRENCY;
    #[ORM\Column(name: 'phone', type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'phone')]
    private ?string $phone = null;
    #[ORM\Column(name: 'fax', type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'fax')]
    private ?string $fax = null;
    #[ORM\Column(name: 'mobile', type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'mobile')]
    private ?string $mobile = null;
    /**
     * Contact email
     */
    #[ORM\Column(name: 'email', type: Types::STRING, length: 75, nullable: true)]
    #[Assert\Length(max: 75)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'email')]
    private ?string $email = null;
    #[ORM\Column(name: 'homepage', type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'homepage')]
    private ?string $homepage = null;
    /**
     * Timezone of begin and end
     */
    #[ORM\Column(name: 'timezone', type: Types::STRING, length: 64, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Timezone]
    #[Assert\Length(max: 64)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'timezone')]
    private ?string $timezone = null;
    /**
     * Meta fields registered with the customer
     *
     * @var Collection<CustomerMeta>
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerMeta::class, cascade: ['persist'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
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
    #[ORM\Column(name: 'invoice_text', type: Types::TEXT, nullable: true)]
    private ?string $invoiceText = null;
    #[ORM\Column(name: 'address_line1', type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'address_line1')]
    private ?string $addressLine1 = null;
    #[ORM\Column(name: 'address_line2', type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'address_line2')]
    private ?string $addressLine2 = null;
    #[ORM\Column(name: 'address_line3', type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'address_line3')]
    private ?string $addressLine3 = null;
    #[ORM\Column(name: 'postcode', type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'postcode')]
    private ?string $postCode = null;
    // this should be more than enough to cover 99.99% - https://en.wikipedia.org/wiki/List_of_long_place_names
    #[ORM\Column(name: 'city', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'city')]
    private ?string $city = null;
    #[ORM\Column(name: 'buyer_reference', type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Customer_Entity'])]
    #[Exporter\Expose(label: 'buyerReference')]
    private ?string $buyerReference = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
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

    public function getFormattedAddress(): ?string
    {
        $address = $this->getAddressLine1();
        if ($this->getAddressLine2() !== null) {
            $address .= PHP_EOL;
            $address .= $this->getAddressLine2();
        }
        if ($this->getAddressLine3() !== null) {
            $address .= PHP_EOL;
            $address .= $this->getAddressLine3();
        }
        if ($this->getPostCode() !== null || $this->getCity() !== null) {
            $address .= PHP_EOL;
            if ($this->getPostCode() !== null) {
                $address .= $this->getPostCode();
                if ($this->getCity() !== null) {
                    $address .= ' ';
                }
            }
            $address .= $this->getCity() ?? '';
        }

        if ($address !== null && $address !== '') {
            return $address;
        }

        return $this->getAddress();
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

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $addressLine1): void
    {
        $this->addressLine1 = $addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getAddressLine3(): ?string
    {
        return $this->addressLine3;
    }

    public function setAddressLine3(?string $addressLine3): void
    {
        $this->addressLine3 = $addressLine3;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(?string $postCode): void
    {
        $this->postCode = $postCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getBuyerReference(): ?string
    {
        return $this->buyerReference;
    }

    public function setBuyerReference(?string $buyerReference): void
    {
        $this->buyerReference = $buyerReference;
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

        $this->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        $currentTeams = $this->teams;
        $this->teams = new ArrayCollection();
        /** @var Team $team */
        foreach ($currentTeams as $team) {
            $this->addTeam($team);
        }

        $this->number = null;
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
