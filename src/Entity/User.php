<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Application main User entity.
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(
 *      name="users",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"username"}),
 *          @ORM\UniqueConstraint(columns={"email"})
 *      }
 * )
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User extends BaseUser implements UserInterface, \JsonSerializable
{
    public const ROLE_CUSTOMER = 'ROLE_CUSTOMER';
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_TEAMLEAD = 'ROLE_TEAMLEAD';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const DEFAULT_ROLE = self::ROLE_USER;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=60, nullable=true)
     * @Assert\Length(max=160)
     */
    private $alias;

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
        parent::__construct();
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
    public function setPreferences($preferences)
    {
        $this->preferences = new ArrayCollection();

        foreach ($preferences as $preference) {
            $this->addPreference($preference);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return UserPreference|null
     */
    public function getPreference(string $name)
    {
        foreach ($this->preferences as $preference) {
            if ($preference->getName() == $name) {
                return $preference;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return bool|int|null|string
     */
    public function getPreferenceValue($name, $default = null)
    {
        $preference = $this->getPreference($name);
        if (null === $preference) {
            return $default;
        }

        return $preference->getValue();
    }

    /**
     * @param UserPreference $preference
     * @return User
     */
    public function addPreference(UserPreference $preference)
    {
        $this->preferences->add($preference);
        $preference->setUser($this);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAlias() ?: $this->getUsername();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'alias' => $this->getAlias(),
            'title' => $this->getTitle(),
            'active' => $this->isEnabled(),
            'avatar' => $this->getAvatar(),
        ];
    }
}
