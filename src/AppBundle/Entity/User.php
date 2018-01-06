<?php

namespace AppBundle\Entity;

use AppBundle\Validator\Constraints as KimaiAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"}), @ORM\UniqueConstraint(name="mail", columns={"mail"})})
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class User implements UserInterface
{

    const DEFAULT_ROLE = 'ROLE_USER';
    const DEFAULT_LANGUAGE = 'de';

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
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=5, nullable=true)
     * @Assert\Language()
     */
    private $language = self::DEFAULT_LANGUAGE;

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
     * User constructor.
     */
    public function __construct()
    {
        $this->registeredAt = new \DateTime();
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
     * @param $registeredAt
     * @return $this
     */
    public function setRegisteredAt($registeredAt)
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
    public function getActive()
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
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
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