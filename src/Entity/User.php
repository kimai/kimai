<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Export\Annotation as Exporter;
use App\Utils\StringHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="kimai2_users",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"username"}),
 *          @ORM\UniqueConstraint(columns={"email"})
 *      }
 * )
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *      "LanguageAsString",
 *      exp="object.getLocale()",
 *      options={
 *          @Serializer\SerializedName("language"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"User_Entity"})
 *      }
 * )
 * @Serializer\VirtualProperty(
 *      "TimezoneAsString",
 *      exp="object.getTimezone()",
 *      options={
 *          @Serializer\SerializedName("timezone"),
 *          @Serializer\Type(name="string"),
 *          @Serializer\Groups({"User_Entity"})
 *      }
 * )
 *
 * @Exporter\Order({"id", "username", "alias", "title", "email", "last_login", "language", "timezone", "active", "registeredAt", "roles", "teams"})
 * @Exporter\Expose("email", label="label.email", exp="object.getEmail()")
 * @Exporter\Expose("username", label="label.username", exp="object.getUsername()")
 * @Exporter\Expose("timezone", label="label.timezone", exp="object.getTimezone()")
 * @Exporter\Expose("language", label="label.language", exp="object.getLanguage()")
 * @Exporter\Expose("last_login", label="label.lastLogin", exp="object.getLastLogin()")
 * @Exporter\Expose("roles", label="label.roles", exp="object.getRoles()", type="array")
 * @ Exporter\Expose("teams", label="label.team", exp="object.getTeams().toArray()", type="array")
 * @Exporter\Expose("active", label="label.active", exp="object.isEnabled()", type="boolean")
 */
class User extends BaseUser implements UserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_TEAMLEAD = 'ROLE_TEAMLEAD';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const DEFAULT_ROLE = self::ROLE_USER;
    public const DEFAULT_LANGUAGE = 'en';

    public const AUTH_INTERNAL = 'kimai';
    public const AUTH_LDAP = 'ldap';
    public const AUTH_SAML = 'saml';

    /**
     * Internal ID
     *
     * @var int
     * @internal must be protected because of parent class
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.id", type="integer")
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;
    /**
     * The user alias will be displayed in the frontend instead of the username
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.alias")
     *
     * @ORM\Column(name="alias", type="string", length=60, nullable=true)
     * @Assert\Length(max=60)
     */
    private $alias;
    /**
     * Registration date for the user
     *
     * @var \DateTime
     *
     * @Exporter\Expose(label="profile.registration_date", type="datetime")
     *
     * @ORM\Column(name="registration_date", type="datetime", nullable=true)
     */
    private $registeredAt;
    /**
     * An additional title for the user, like the Job position or Department
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"User_Entity"})
     *
     * @Exporter\Expose(label="label.title")
     *
     * @ORM\Column(name="title", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private $title;
    /**
     * URL to the users avatar, will be auto-generated if empty
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"User_Entity"})
     *
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private $avatar;
    /**
     * API token (password) for this user
     *
     * @var string
     *
     * @ORM\Column(name="api_token", type="string", length=255, nullable=true)
     */
    private $apiToken;
    /**
     * @var string
     * @internal to be set via form, must not be persisted
     */
    private $plainApiToken;
    /**
     * User preferences
     *
     * List of preferences for this user, required ones have dedicated fields/methods
     *
     * @var UserPreference[]|Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserPreference", mappedBy="user", cascade={"persist"})
     */
    private $preferences;
    /**
     * All teams of the user
     *
     * @var Team[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"User_Entity"})
     *
     * @ORM\ManyToMany(targetEntity="Team", inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(
     *  name="kimai2_users_teams",
     *  joinColumns={
     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="CASCADE")
     *  }
     * )
     */
    private $teams;
    /**
     * The type of authentication used by the user (eg. "kimai", "ldap", "saml")
     *
     * @var string
     * @internal for internal usage only
     *
     * @ORM\Column(name="auth", type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $auth = self::AUTH_INTERNAL;
    /**
     * This flag will be initialized in UserEnvironmentSubscriber.
     *
     * @var bool|null
     * @internal has no database mapping as the value is calculated from a permission
     */
    private $isAllowedToSeeAllData = null;

    public function __construct()
    {
        parent::__construct();
        $this->registeredAt = new \DateTime();
        $this->preferences = new ArrayCollection();
        $this->teams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegisteredAt(): ?\DateTime
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTime $registeredAt): User
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function setAlias(?string $alias): User
    {
        $this->alias = StringHelper::ensureMaxLength($alias, 60);

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): User
    {
        $this->title = StringHelper::ensureMaxLength($title, 50);

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): User
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): User
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getPlainApiToken(): ?string
    {
        return $this->plainApiToken;
    }

    public function setPlainApiToken(?string $plainApiToken): User
    {
        $this->plainApiToken = $plainApiToken;

        return $this;
    }

    /**
     * @return Collection<UserPreference>
     */
    public function getPreferences(): Collection
    {
        return $this->preferences;
    }

    /**
     * @param iterable<UserPreference> $preferences
     * @return User
     */
    public function setPreferences(iterable $preferences): User
    {
        $this->preferences = new ArrayCollection();

        foreach ($preferences as $preference) {
            $this->addPreference($preference);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param bool|int|string|null $value
     */
    public function setPreferenceValue(string $name, $value = null)
    {
        $pref = $this->getPreference($name);

        if (null === $pref) {
            $pref = new UserPreference();
            $pref->setName($name);
            $this->addPreference($pref);
        }

        $pref->setValue($value);
    }

    public function getPreference(string $name): ?UserPreference
    {
        // this code will be triggered, if a currently logged-in user will be deleted and the refreshed from the session
        // via one of the UserProvider - e.g. see LdapUserProvider::refreshUser() which calls $user->getPreferenceValue()
        if (empty($this->preferences)) {
            return null;
        }

        foreach ($this->preferences as $preference) {
            if ($preference->getName() == $name) {
                return $preference;
            }
        }

        return null;
    }

    public function getLocale(): string
    {
        return $this->getPreferenceValue(UserPreference::LOCALE, User::DEFAULT_LANGUAGE);
    }

    public function getTimezone(): string
    {
        return $this->getPreferenceValue(UserPreference::TIMEZONE, date_default_timezone_get());
    }

    public function getLanguage(): string
    {
        return $this->getLocale();
    }

    public function setLanguage(?string $language)
    {
        if ($language === null) {
            $language = User::DEFAULT_LANGUAGE;
        }
        $this->setPreferenceValue(UserPreference::LOCALE, $language);
    }

    public function setTimezone(?string $timezone)
    {
        if ($timezone === null) {
            $timezone = date_default_timezone_get();
        }
        $this->setPreferenceValue(UserPreference::TIMEZONE, $timezone);
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
    public function addPreference(UserPreference $preference): User
    {
        if (null === $this->preferences) {
            $this->preferences = new ArrayCollection();
        }

        $this->preferences->add($preference);
        $preference->setUser($this);

        return $this;
    }

    public function addTeam(Team $team): User
    {
        if ($this->teams->contains($team)) {
            return $this;
        }

        $this->teams->add($team);
        $team->addUser($this);

        return $this;
    }

    public function removeTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            return;
        }
        $this->teams->removeElement($team);
        $team->removeUser($this);
    }

    public function hasTeamAssignment(): bool
    {
        return !$this->getTeams()->isEmpty();
    }

    /**
     * @return Collection<Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function isInTeam(Team $team): bool
    {
        return $this->teams->contains($team);
    }

    public function isTeamleadOf(Team $team): bool
    {
        return $team->getTeamLead() === $this;
    }

    public function canSeeAllData(): bool
    {
        return $this->isSuperAdmin() || true === $this->isAllowedToSeeAllData;
    }

    /**
     * This method should not be called by plugins and returns true on success or false on a failure.
     *
     * @internal immutable property that cannot be set by plugins
     * @param bool $canSeeAllData
     * @return bool
     * @throws \Exception
     */
    public function initCanSeeAllData(bool $canSeeAllData): bool
    {
        // prevent manipulation from plugins
        if (null !== $this->isAllowedToSeeAllData) {
            return false;
        }

        $this->isAllowedToSeeAllData = $canSeeAllData;

        return true;
    }

    public function isTeamlead(): bool
    {
        return $this->hasRole(static::ROLE_TEAMLEAD);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(static::ROLE_ADMIN);
    }

    public function getDisplayName(): ?string
    {
        if (!empty($this->getAlias())) {
            return $this->getAlias();
        }

        return $this->getUsername();
    }

    public function getAuth(): ?string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): User
    {
        $this->auth = $auth;

        return $this;
    }

    public function isSamlUser(): bool
    {
        return $this->auth === self::AUTH_SAML;
    }

    public function isLdapUser(): bool
    {
        return $this->auth === self::AUTH_LDAP;
    }

    public function isInternalUser(): bool
    {
        return $this->auth === null || $this->auth === self::AUTH_INTERNAL;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }
}
