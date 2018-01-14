<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserPreference
 *
 * @ORM\Entity()
 * @ORM\Table(
 *      name="user_preferences",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "name"})
 *      }
 * )
 */
class UserPreference
{
    const HOURLY_RATE = 'hourly_rate';
    const SKIN = 'skin';

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
    protected $type = TextType::class;

    /**
     * @var Constraint[]
     */
    protected $constraints = [];

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

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UserPreference
     */
    public function setUser(User $user): UserPreference
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return UserPreference
     */
    public function setName(string $name): UserPreference
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return UserPreference
     */
    public function setValue(string $value): UserPreference
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
    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
}
