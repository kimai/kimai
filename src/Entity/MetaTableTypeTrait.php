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
    /**
     * @Serializer\Exclude()
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;
    /**
     * Name of the meta (custom) field
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=50)
     */
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private ?string $name = null;
    /**
     * Value of the meta (custom) field
     *
     * This field can be used to temporary hold data in another format (e.g. array) during form transformation.
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Assert\Length(max=65535)
     */
    #[ORM\Column(name: 'value', type: 'text', length: 65535, nullable: true)]
    private ?string $value = null;
    /**
     * @Assert\NotNull()
     */
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false, options: ['default' => false])]
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
     * @var array
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

    /**
     * @return int|bool|string|float|null
     */
    public function getValue()
    {
        return match ($this->type) {
            YesNoType::class, CheckboxType::class => (bool) $this->value,
            IntegerType::class => (int) $this->value,
            NumberType::class => (float) $this->value,
            default => $this->value,
        };
    }

    /**
     * Value will not be serialized before its stored, so it should be a primitive type.
     *
     * @param mixed $value
     * @return MetaTableTypeInterface
     */
    public function setValue($value): MetaTableTypeInterface
    {
        // unchecked checkboxes / false bool would save an empty string in the database
        // those cannot be searched in the database
        if (null !== $value) {
            switch ($this->type) {
                case YesNoType::class:
                case CheckboxType::class:
                    $value = (int) $value;
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

    public function setLabel(string $label): MetaTableTypeInterface
    {
        $this->label = $label;

        return $this;
    }

    public function setOptions(array $options): MetaTableTypeInterface
    {
        $this->options = $options;

        return $this;
    }

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
}
