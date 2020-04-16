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

/**
 * @ORM\Entity()
 * @ORM\Table(name="kimai2_user_preferences",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "name"})
 *      }
 * )
 */
class UserPreference
{
    public const HOURLY_RATE = 'hourly_rate';
    public const INTERNAL_RATE = 'internal_rate';
    public const SKIN = 'skin';
    public const LOCALE = 'language';
    public const TIMEZONE = 'timezone';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="preferences")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull()
     */
    private $user;
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
     * @var string
     */
    private $type;
    /**
     * @var bool
     */
    private $enabled = true;
    /**
     * @var Constraint[]
     */
    private $constraints = [];
    /**
     * An array of options for the form element
     * @var array
     */
    private $options = [];
    /**
     * @var int
     */
    private $order = 1000;

    /**
     * @return int
     */
    public function getId()
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
        return $this->name;
    }

    public function setName(string $name): UserPreference
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
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
     * Given $value will not be serialized before its stored, so it should be one of the types:
     * integer, string or boolean
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
    public function getConstraints()
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
}
