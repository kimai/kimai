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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

trait MetaTableTypeTrait
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     * @Assert\NotNull()
     * @Assert\Length(min=2, max=50, allowEmptyString=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=false, options={"default": false})
     * @Assert\NotNull()
     */
    private $visible = false;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var Constraint[]
     */
    private $constraints = [];

    /**
     * An array of options for the form element
     * @var array
     */
    private $options = [];

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
     * @return mixed|null
     */
    public function getValue()
    {
        switch ($this->type) {
            case YesNoType::class:
            case CheckboxType::class:
                return (bool) $this->value;
            case IntegerType::class:
                return (int) $this->value;
        }

        return $this->value;
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
            ->setType($meta->getType())
            ->setConstraints($meta->getConstraints())
            ->setIsRequired($meta->isRequired())
            ->setIsVisible($meta->isVisible())
            ->setLabel($meta->getLabel())
            ->setOptions($meta->getOptions())
        ;

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

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
