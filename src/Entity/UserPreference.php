<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Form\Type\YesNoType;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_user_preferences')]
#[ORM\UniqueConstraint(columns: ['user_id', 'name'])]
#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class UserPreference
{
    public const HOURLY_RATE = 'hourly_rate';
    public const INTERNAL_RATE = 'internal_rate';
    public const SKIN = 'skin';
    public const LOCALE = 'language';
    public const TIMEZONE = 'timezone';
    public const FIRST_WEEKDAY = 'first_weekday';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'preferences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private string $name;
    #[ORM\Column(name: 'value', type: 'string', length: 255, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $value;
    private ?string $type = null;
    private bool $enabled = true;
    /**
     * @var Constraint[]
     */
    private array $constraints = [];
    /**
     * An array of options for the form element
     * @var array
     */
    private array $options = [];
    private int $order = 1000;
    private string $section = 'default';

    public function __construct(string $name, string|int|float|bool|null $value = null)
    {
        $this->name = $this->sanitizeName($name);
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return UserPreference
     */
    public function setId(int $id): UserPreference
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): UserPreference
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->sanitizeName($this->name);
    }

    public function matches(string $name): bool
    {
        return $this->sanitizeName($name) === $this->getName();
    }

    public function sanitizeName(?string $name): string
    {
        return str_replace(['.', '-'], '_', $name);
    }

    public function getValue(): bool|int|float|string|null
    {
        return match ($this->type) {
            YesNoType::class, CheckboxType::class => (bool) $this->value,
            IntegerType::class => (int) $this->value,
            NumberType::class => (float) $this->value,
            default => $this->value,
        };
    }

    /**
     * Given $value will not be serialized before its stored, so it should be one of the types:
     * integer, float, string, boolean or null
     *
     * @param mixed $value
     * @return UserPreference
     */
    public function setValue($value): UserPreference
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the form type to edit that setting.
     *
     * @param string $type
     * @return UserPreference
     */
    public function setType(string $type): UserPreference
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): UserPreference
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Set the constraints which are used for validation of the value.
     *
     * @param Constraint[] $constraints
     * @return $this
     */
    public function setConstraints(array $constraints)
    {
        $this->constraints = $constraints;

        return $this;
    }

    /**
     * Adds a constraint which is used for validation of the value.
     *
     * @param Constraint $constraint
     * @return $this
     */
    public function addConstraint(Constraint $constraint)
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /**
     * @return Constraint[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Set an array of options for the FormType.
     *
     * @param array $options
     * @return UserPreference
     */
    public function setOptions(array $options): UserPreference
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns an array with options for the FormType.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getLabel(): ?string
    {
        if (isset($this->options['label'])) {
            return $this->options['label'];
        }

        return $this->name;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): UserPreference
    {
        $this->order = $order;

        return $this;
    }

    public function setSection(string $section): UserPreference
    {
        $this->section = $section;

        return $this;
    }

    public function getSection(): string
    {
        return $this->section;
    }
}
