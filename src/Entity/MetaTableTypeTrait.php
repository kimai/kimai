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

trait MetaTableTypeTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[Serializer\Exclude]
    private ?int $id = null;
    /**
     * Name of the meta (custom) field
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $name = null;
    /**
     * Value of the meta (custom) field
     *
     * ATTENTION:
     * This field can be used to temporary hold data in another format (e.g. array) during form transformation,
     */
    #[ORM\Column(name: 'value', type: 'text', length: 65535, nullable: true)]
    #[Assert\Length(max: 65535)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private mixed $value = null;
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Assert\NotNull]
    private bool $visible = false;
    private ?string $label = null;
    private ?string $type = null;
    private bool $required = false;
    /**
     * @var Constraint[]
     */
    private array $constraints = [];
    /**
     * An array of options for the form element
     * @var array<string, mixed>
     */
    private array $options = [];
    private int $order = 0;

    public function getName(): ?string
    {
        if (null === $this->name) {
            return null;
        }

        return strtolower($this->name);
    }

    public function setName(string $name): MetaTableTypeInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            YesNoType::class, CheckboxType::class => (\is_string($this->value) || \is_int($this->value)) ? (bool) $this->value : $this->value,
            IntegerType::class => (\is_string($this->value) || \is_int($this->value)) ? (int) $this->value : $this->value,
            NumberType::class => (\is_string($this->value) || \is_float($this->value)) ? (float) $this->value : $this->value,
            default => $this->value,
        };
    }

    /**
     * Value will not be serialized before its stored, so it should be a primitive type.
     *
     * @param mixed $value
     * @return MetaTableTypeInterface
     */
    public function setValue(mixed $value): MetaTableTypeInterface
    {
        // unchecked checkboxes / false bool would save an empty string in the database
        // those cannot be searched in the database
        if (null !== $value) {
            switch ($this->type) {
                case YesNoType::class:
                case CheckboxType::class:
                    if (\is_string($value) || \is_bool($value)) {
                        $value = (int) $value;
                    } else {
                        throw new \InvalidArgumentException('Failed converting meta-field bool value');
                    }
            }
        }

        $this->value = $value;

        return $this;
    }

    public function setConstraints(array $constraints): MetaTableTypeInterface
    {
        $this->constraints = [];

        foreach ($constraints as $constraint) {
            $this->addConstraint($constraint);
        }

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): MetaTableTypeInterface
    {
        $this->type = $type;

        return $this;
    }

    public function addConstraint(Constraint $constraint): MetaTableTypeInterface
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

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setIsRequired(bool $isRequired): MetaTableTypeInterface
    {
        $this->required = $isRequired;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setIsVisible(bool $visible): MetaTableTypeInterface
    {
        $this->visible = $visible;

        return $this;
    }

    public function merge(MetaTableTypeInterface $meta): MetaTableTypeInterface
    {
        $this
            ->setConstraints($meta->getConstraints())
            ->setIsRequired($meta->isRequired())
            ->setIsVisible($meta->isVisible())
            ->setOptions($meta->getOptions())
            ->setOrder($meta->getOrder())
        ;

        if ($meta->getLabel() !== null) {
            $this->setLabel($meta->getLabel());
        }

        if ($meta->getType() !== null) {
            $this->setType($meta->getType());
        }

        return $this;
    }

    public function getLabel(): ?string
    {
        if (null === $this->label) {
            return $this->name;
        }

        return $this->label;
    }

    public function setLabel(?string $label): MetaTableTypeInterface
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     * @return MetaTableTypeInterface
     */
    public function setOptions(array $options): MetaTableTypeInterface
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): MetaTableTypeInterface
    {
        $this->order = $order;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }

    /**
     * Whether this field is defined by a plugin or just a value stored in the database.
     */
    public function isDefined(): bool
    {
        return $this->type !== null;
    }
}
