<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

trait MetaTableTrait
{
    /**
     * @var int
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
     * @Assert\Length(min=2, max=50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var bool
     */
    private $export = true;

    /**
     * @var Constraint[]
     */
    private $constraints = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): MetaTableTypeInterface
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    /**
     * Do not expose data which returns false here (eg. in export or API).
     *
     * @return bool
     */
    public function isPublicVisible(): bool
    {
        return $this->export;
    }

    public function setIsPublicVisible(bool $include): MetaTableTypeInterface
    {
        $this->export = $include;

        return $this;
    }

    public function __toString()
    {
        if (null !== $this->value) {
            return (string) $this->value;
        }

        return '';
    }
}
