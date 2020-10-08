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
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_customers",
 *     indexes={
 *          @ORM\Index(columns={"visible"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\CustomerRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @Exporter\Order({"id", "name", "company", "number", "vatId", "address", "contact","email", "phone", "mobile", "fax", "homepage", "country", "currency", "timezone", "budget", "timeBudget", "color", "visible", "teams", "comment"})
 * @ Exporter\Expose("teams", label="label.team", exp="object.getTeams().toArray()", type="array")
 */
class Customer implements EntityWithMetaFields
{
    public const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var int|null
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.id", type="integer")
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.name")
     *
     * @ORM\Column(name="name", type="string", length=150, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=150, allowEmptyString=false)
     */
    private $name;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.number")
     *
     * @ORM\Column(name="number", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private $number;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.comment")
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;
    /**
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.visible", type="boolean")
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $visible = true;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.company")
     *
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $company;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.vat_id")
     *
     * @ORM\Column(name="vat_id", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private $vatId;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.contact")
     *
     * @ORM\Column(name="contact", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $contact;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.address")
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     */
    private $address;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.country")
     *
     * @ORM\Column(name="country", type="string", length=2, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=2)
     */
    private $country;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     *
     * @Exporter\Expose(label="label.currency")
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=3)
     */
    private $currency = self::DEFAULT_CURRENCY;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.phone")
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $phone;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.fax")
     *
     * @ORM\Column(name="fax", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $fax;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.mobile")
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $mobile;
    /**
     * Customers contact email
     *
     * Limited via RFC to 254 chars
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.email")
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * @Assert\Length(max=254)
     */
    private $email;
    /**
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.homepage")
     *
     * @ORM\Column(name="homepage", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $homepage;
    /**
     * Timezone of begin and end
     *
     * Length was determined by a MySQL column via "use mysql;describe time_zone_name;"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @Exporter\Expose(label="label.timezone")
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=64)
     */
    private $timezone;

    // keep the trait include exactly here, for placing the column at the correct position
    use ColorTrait;

    /**
     * The total monetary budget, will be zero if not configured.
     *
     * @var float
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @ Exporter\Expose(label="label.budget")
     *
     * @ORM\Column(name="budget", type="float", nullable=false)
     * @Assert\NotNull()
     */
    private $budget = 0.00;
    /**
     * The time budget in seconds, will be be zero if not configured.
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer_Entity"})
     *
     * @ Exporter\Expose(label="label.timeBudget", type="duration")
     *
     * @ORM\Column(name="time_budget", type="integer", nullable=false)
     * @Assert\NotNull()
     */
    private $timeBudget = 0;
    /**
     * Meta fields
     *
     * All visible meta (custom) fields registered with this customer
     *
     * @var CustomerMeta[]|Collection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     * @Serializer\Type(name="array<App\Entity\CustomerMeta>")
     * @Serializer\SerializedName("metaFields")
     * @Serializer\Accessor(getter="getVisibleMetaFields")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerMeta", mappedBy="customer", cascade={"persist"})
     */
    private $meta;
    /**
     * Teams
     *
     * If no team is assigned, everyone can access the customer
     *
     * @var Team[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Customer"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Team"))
     *
     * @ORM\ManyToMany(targetEntity="Team", cascade={"persist"}, inversedBy="customers")
     * @ORM\JoinTable(
     *  name="kimai2_customers_teams",
     *  joinColumns={
     *      @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="CASCADE")
     *  }
     * )
     */
    private $teams;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->teams = new ArrayCollection();
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

    public function isVisible(): bool
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

    public function setBudget(float $budget): Customer
    {
        $this->budget = $budget;

        return $this;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function setTimeBudget(int $seconds): Customer
    {
        $this->timeBudget = $seconds;

        return $this;
    }

    public function getTimeBudget(): int
    {
        return $this->timeBudget;
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

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
