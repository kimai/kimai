<?php

namespace App\Entity;

use App\Validator\Constraints as KimaiAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Application main User entity.
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(
 *      name="users",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"}),
 *          @ORM\UniqueConstraint(columns={"mail"})
 *      }
 * )
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class User implements UserInterface, AdvancedUserInterface
{

    const DEFAULT_ROLE = 'ROLE_USER';

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
     * @ORM\Column(name="name", type="string", length=60, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min=6, max=160)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="mail", type="string", length=160, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=254, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"registration", "passwordUpdate"})
     * @Assert\Length(min=6, max=4096, groups={"registration", "passwordUpdate"})
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=60, nullable=true)
     * @Assert\Length(max=160)
     */
    private $alias;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     * @Assert\NotNull()
     */
    private $active = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="datetime", nullable=true)
     */
    private $registeredAt;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=50, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @var string[]
     *
     * @ORM\Column(type="json_array")
     * @KimaiAssert\Role()
     */
    private $roles = [];

    /**
     * @var UserPreference[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserPreference", mappedBy="user", cascade={"persist"})
     */
    private $preferences;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->registeredAt = new \DateTime();
        $this->preferences = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * @param \DateTime $registeredAt
     * @return $this
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Only for form editing, you don't need this method!
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Only for form editing, you don't need this method!
     *
     * @param string $password
     * @return $this
     */
    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     *
     * @return string[]
     */
    public function getRoles()
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * @param string[] $roles
     * @return $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return UserPreference[]|Collection
     */
    public function getPreferences(): Collection
    {
        return $this->preferences;
    }

    /**
     * @param UserPreference[]|Collection<UserPreference> $preferences
     * @return User
     */
    public function setPreferences(array $preferences)
    {
        if (!($preferences instanceof Collection) && is_array($preferences)) {
            $preferences = new ArrayCollection($preferences);
        }
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * @param UserPreference $preference
     * @return User
     */
    public function addPreference(UserPreference $preference)
    {
        $this->preferences->add($preference);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isAccountNonExpired()
    {
        return $this->isEnabled();
    }

    /**
     * @inheritdoc
     */
    public function isAccountNonLocked()
    {
        return $this->isEnabled();
    }

    /**
     * @inheritdoc
     */
    public function isCredentialsNonExpired()
    {
        return $this->isEnabled();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isActive();
    }

    /**
     * Returns the salt that was originally used to encode the password.
     */
    public function getSalt()
    {
        // See "Do you need to use a Salt?" at http://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAlias() ?: $this->getUsername();
    }
}
