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
use App\Validator\Constraints as Constraints;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JMS\Serializer\Annotation as Serializer;
use KevinPapst\TablerBundle\Model\UserInterface as ThemeUserInterface;
use OpenApi\Attributes as OA;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_users')]
#[ORM\UniqueConstraint(columns: ['username'])]
#[ORM\UniqueConstraint(columns: ['email'])]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('username')]
#[UniqueEntity('email')]
#[Serializer\ExclusionPolicy('all')]
#[Exporter\Order(['id', 'username', 'alias', 'title', 'email', 'last_login', 'language', 'timezone', 'active', 'registeredAt', 'roles', 'teams', 'color', 'accountNumber'])]
#[Exporter\Expose(name: 'email', label: 'email', exp: 'object.getEmail()')]
#[Exporter\Expose(name: 'username', label: 'username', exp: 'object.getUserIdentifier()')]
#[Exporter\Expose(name: 'timezone', label: 'timezone', exp: 'object.getTimezone()')]
#[Exporter\Expose(name: 'language', label: 'language', exp: 'object.getLanguage()')]
#[Exporter\Expose(name: 'last_login', label: 'lastLogin', type: 'datetime', exp: 'object.getLastLogin()')]
#[Exporter\Expose(name: 'roles', label: 'roles', type: 'array', exp: 'object.getRoles()')]
#[Exporter\Expose(name: 'active', label: 'active', type: 'boolean', exp: 'object.isEnabled()')]
#[Constraints\User(groups: ['UserCreate', 'Registration', 'Default', 'Profile'])]
class User implements UserInterface, EquatableInterface, ThemeUserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_TEAMLEAD = 'ROLE_TEAMLEAD';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const DEFAULT_ROLE = self::ROLE_USER;
    public const DEFAULT_LANGUAGE = 'en';
    public const DEFAULT_FIRST_WEEKDAY = 'monday';

    public const AUTH_INTERNAL = 'kimai';
    public const AUTH_LDAP = 'ldap';
    public const AUTH_SAML = 'saml';

    public const WIZARDS = ['intro', 'profile'];

    /**
     * Unique User ID
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'id', type: 'integer')]
    private ?int $id = null;
    /**
     * The user alias will be displayed in the frontend instead of the username
     */
    #[ORM\Column(name: 'alias', type: 'string', length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'alias')]
    private ?string $alias = null;
    /**
     * Registration date for the user
     */
    #[ORM\Column(name: 'registration_date', type: 'datetime', nullable: true)]
    #[Exporter\Expose(label: 'profile.registration_date', type: 'datetime')]
    private ?\DateTime $registeredAt = null;
    /**
     * An additional title for the user, like the Job position or Department
     */
    #[ORM\Column(name: 'title', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'title')]
    private ?string $title = null;
    /**
     * URL to the user avatar, will be auto-generated if empty
     */
    #[ORM\Column(name: 'avatar', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['Profile'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['User_Entity'])]
    private ?string $avatar = null;
    /**
     * API token (password) for this user
     */
    #[ORM\Column(name: 'api_token', type: 'string', length: 255, nullable: true)]
    private ?string $apiToken = null;
    /**
     * @internal to be set via form, must not be persisted
     */
    #[Assert\NotBlank(groups: ['ApiTokenUpdate'])]
    #[Assert\Length(min: 8, max: 60, groups: ['ApiTokenUpdate'])]
    private ?string $plainApiToken = null;
    /**
     * User preferences
     *
     * List of preferences for this user, required ones have dedicated fields/methods
     *
     * This Collection can be null for one edge case ONLY:
     * if a currently logged-in user will be deleted and then refreshed from the session from one of the UserProvider
     * e.g. see LdapUserProvider::refreshUser() it might crash if $user->getPreferenceValue() is called
     *
     * @var Collection<UserPreference>|null
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPreference::class, cascade: ['persist'])]
    private ?Collection $preferences = null;
    /**
     * List of all team memberships.
     *
     * @var Collection<TeamMember>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TeamMember::class, cascade: ['persist'], fetch: 'LAZY', orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/TeamMembership'))]
    private Collection $memberships;
    /**
     * The type of authentication used by the user (e.g. "kimai", "ldap", "saml")
     *
     * @internal for internal usage only
     */
    #[ORM\Column(name: 'auth', type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $auth = self::AUTH_INTERNAL;
    /**
     * This flag will be initialized in UserEnvironmentSubscriber.
     *
     * @internal has no database mapping as the value is calculated from a permission
     */
    private ?bool $isAllowedToSeeAllData = null;
    #[ORM\Column(name: 'username', type: 'string', length: 180, nullable: false)]
    #[Assert\NotBlank(groups: ['Registration', 'UserCreate', 'Profile'])]
    #[Assert\Regex(pattern: '/\//', match: false, groups: ['Registration', 'UserCreate', 'Profile'])]
    #[Assert\Length(min: 2, max: 64, groups: ['Registration', 'UserCreate', 'Profile'])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $username = null;
    #[ORM\Column(name: 'email', type: 'string', length: 180, nullable: false)]
    #[Assert\NotBlank(groups: ['Registration', 'UserCreate', 'Profile'])]
    #[Assert\Length(min: 2, max: 180)]
    #[Assert\Email(mode: 'html5', groups: ['Registration', 'UserCreate', 'Profile'])]
    private ?string $email = null;
    #[ORM\Column(name: 'account', type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'account_number')]
    private ?string $accountNumber = null;
    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $enabled = false;
    /**
     * Encrypted password. Must be persisted.
     */
    #[ORM\Column(name: 'password', type: 'string', nullable: false)]
    private ?string $password = null;
    /**
     * Plain password. Used for model validation, not persisted.
     */
    #[Assert\NotBlank(groups: ['Registration', 'PasswordUpdate', 'UserCreate'])]
    #[Assert\Length(min: 8, max: 60, groups: ['Registration', 'PasswordUpdate', 'UserCreate', 'ResetPassword', 'ChangePassword'])]
    private ?string $plainPassword = null;
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?DateTime $lastLogin = null;
    /**
     * Random string sent to the user email address in order to verify it.
     */
    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 180, unique: true, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $confirmationToken = null;
    #[ORM\Column(name: 'password_requested_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordRequestedAt = null;
    /**
     * List of all role names
     */
    #[ORM\Column(name: 'roles', type: 'array', nullable: false)]
    #[Serializer\Expose]
    #[Serializer\Groups(['User_Entity'])]
    #[Serializer\Type('array<string>')]
    #[Constraints\Role(groups: ['RolesUpdate'])]
    private array $roles = [];
    /**
     * If not empty two-factor authentication is enabled.
     * TODO reduce the length, which was initially forgotten and set to 255, as this is the default for MySQL with Doctrine (see migration Version20230126002049)
     */
    #[ORM\Column(name: 'totp_secret', type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret = null;
    #[ORM\Column(name: 'totp_enabled', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $totpEnabled = false;
    #[ORM\Column(name: 'system_account', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $systemAccount = false;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Serializer\Expose]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(ref: '#/components/schemas/User')]
    private ?User $supervisor = null;

    use ColorTrait;

    public function __construct()
    {
        $this->registeredAt = new DateTime();
        $this->preferences = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegisteredAt(): ?DateTime
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTime $registeredAt): User
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

    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('apiToken')]
    #[Serializer\Groups(['Default'])]
    public function hasApiToken(): bool
    {
        return $this->apiToken !== null;
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
     * Read-only list of all visible user preferences.
     *
     * @internal only for API usage
     * @return UserPreference[]
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('preferences')]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/UserPreference'))]
    public function getVisiblePreferences(): array
    {
        // hide all internal preferences, which are either available in other fields
        // or which are only used within the Kimai UI
        $skip = [
            UserPreference::TIMEZONE,
            UserPreference::LOCALE,
            UserPreference::LANGUAGE,
            UserPreference::SKIN,
            'calendar_initial_view',
            'login_initial_view',
            'update_browser_title',
            'daily_stats',
            'export_decimal',
        ];

        $all = [];
        foreach ($this->preferences as $preference) {
            if ($preference->isEnabled() && !\in_array($preference->getName(), $skip)) {
                $all[] = $preference;
            }
        }

        return $all;
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
     * @param bool|int|string|float|null $value
     */
    public function setPreferenceValue(string $name, $value = null): void
    {
        $pref = $this->getPreference($name);

        if (null === $pref) {
            $pref = new UserPreference($name);
            $this->addPreference($pref);
        }

        $pref->setValue($value);
    }

    public function getPreference(string $name): ?UserPreference
    {
        if ($this->preferences === null) {
            return null;
        }

        foreach ($this->preferences as $preference) {
            if ($preference->matches($name)) {
                return $preference;
            }
        }

        return null;
    }

    /**
     * The locale used for formatting number, money, dates and times
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('locale')]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'string')]
    public function getLocale(): string
    {
        // uses language as fallback, because the language was here before
        return (string) $this->getPreferenceValue(UserPreference::LOCALE, $this->getLanguage(), false);
    }

    public function setLocale(?string $locale): void
    {
        $this->setPreferenceValue(UserPreference::LOCALE, $locale ?? User::DEFAULT_LANGUAGE);
    }

    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('timezone')]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'string')]
    public function getTimezone(): string
    {
        return $this->getPreferenceValue(UserPreference::TIMEZONE, date_default_timezone_get(), false);
    }

    /**
     * The locale used for translations
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('language')]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'string')]
    public function getLanguage(): string
    {
        return (string) $this->getPreferenceValue(UserPreference::LANGUAGE, User::DEFAULT_LANGUAGE, false);
    }

    public function setLanguage(?string $language): void
    {
        $this->setPreferenceValue(UserPreference::LANGUAGE, $language ?? User::DEFAULT_LANGUAGE);
    }

    public function isFirstDayOfWeekSunday(): bool
    {
        return $this->getFirstDayOfWeek() === 'sunday';
    }

    public function getFirstDayOfWeek(): string
    {
        return $this->getPreferenceValue(UserPreference::FIRST_WEEKDAY, User::DEFAULT_FIRST_WEEKDAY, false);
    }

    public function isExportDecimal(): bool
    {
        return (bool) $this->getPreferenceValue('export_decimal', false, false);
    }

    public function getSkin(): string
    {
        return (string) $this->getPreferenceValue(UserPreference::SKIN, 'default', false);
    }

    public function setTimezone(?string $timezone): void
    {
        if ($timezone === null) {
            $timezone = date_default_timezone_get();
        }
        $this->setPreferenceValue(UserPreference::TIMEZONE, $timezone);
    }

    /**
     * @param string $name
     * @param bool|int|float|string|null $default
     * @param bool $allowNull
     * @return bool|int|float|string|null
     */
    public function getPreferenceValue(string $name, mixed $default = null, bool $allowNull = true): bool|int|float|string|null
    {
        $preference = $this->getPreference($name);
        if (null === $preference) {
            return $default;
        }

        $value = $preference->getValue();

        return $allowNull ? $value : ($value ?? $default);
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

    public function addMembership(TeamMember $member): void
    {
        if ($this->memberships->contains($member)) {
            return;
        }

        if ($member->getUser() === null) {
            $member->setUser($this);
        }

        if ($member->getUser() !== $this) {
            throw new \InvalidArgumentException('Cannot set foreign user membership');
        }

        // when using the API an invalid Team ID triggers the validation too late
        if (($team = $member->getTeam()) === null) {
            return;
        }

        if (null !== $this->findMemberByTeam($team)) {
            return;
        }

        $this->memberships->add($member);
        $team->addMember($member);
    }

    private function findMemberByTeam(Team $team): ?TeamMember
    {
        foreach ($this->memberships as $member) {
            if ($member->getTeam() === $team) {
                return $member;
            }
        }

        return null;
    }

    public function removeMembership(TeamMember $member): void
    {
        if (!$this->memberships->contains($member)) {
            return;
        }

        $this->memberships->removeElement($member);
        if ($member->getTeam() !== null) {
            $member->getTeam()->removeMember($member);
        }
        $member->setUser(null);
        $member->setTeam(null);
    }

    /**
     * @return Collection<TeamMember>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function hasMembership(TeamMember $member): bool
    {
        return $this->memberships->contains($member);
    }

    /**
     * Checks if the user is member of any team.
     *
     * @return bool
     */
    public function hasTeamAssignment(): bool
    {
        return !$this->memberships->isEmpty();
    }

    /**
     * Checks is the user is teamlead in any of the assigned teams.
     *
     * @see User::hasTeamleadRole()
     * @return bool
     */
    public function isTeamlead(): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->isTeamlead()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given user is a team member.
     *
     * @param User $user
     * @return bool
     */
    public function hasTeamMember(User $user): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getTeam() !== null && $membership->getTeam()->hasUser($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Use this function to check if the current user can read data from the given user.
     */
    public function canSeeUser(User $user): bool
    {
        if ($user->getId() === $this->getId()) {
            return true;
        }

        if ($this->canSeeAllData()) {
            return true;
        }

        if (!$user->isEnabled()) {
            return false;
        }

        if (!$this->isSystemAccount() && $user->isSystemAccount()) {
            return false;
        }

        if ($this->isTeamleadOfUser($user)) {
            return true;
        }

        return false;
    }

    /**
     * List of all teams, this user is part of
     *
     * @return Team[]
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('teams')]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Team'))]
    public function getTeams(): iterable
    {
        $teams = [];
        foreach ($this->memberships as $membership) {
            $teams[] = $membership->getTeam();
        }

        return $teams;
    }

    /**
     * Required in the User profile screen to edit his teams.
     *
     * @param Team $team
     */
    public function addTeam(Team $team): void
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getTeam() === $team) {
                return;
            }
        }

        $membership = new TeamMember();
        $membership->setUser($this);
        $membership->setTeam($team);

        $this->addMembership($membership);
    }

    /**
     * Required in the User profile screen to edit his teams.
     *
     * @param Team $team
     */
    public function removeTeam(Team $team): void
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getTeam() === $team) {
                $this->removeMembership($membership);

                return;
            }
        }
    }

    public function isInTeam(Team $team): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getTeam() === $team) {
                return true;
            }
        }

        return false;
    }

    public function isTeamleadOf(Team $team): bool
    {
        if (null !== ($member = $this->findMemberByTeam($team))) {
            return $member->isTeamlead();
        }

        return false;
    }

    public function isTeamleadOfUser(User $user): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->isTeamlead() && $membership->getTeam() !== null && $membership->getTeam()->hasUser($user)) {
                return true;
            }
        }

        return false;
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
     * @throws Exception
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

    public function hasTeamleadRole(): bool
    {
        return $this->hasRole(static::ROLE_TEAMLEAD);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(static::ROLE_ADMIN);
    }

    public function getDisplayName(): string
    {
        if (!empty($this->getAlias())) {
            return $this->getAlias();
        }

        return $this->getUserIdentifier();
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

    public function addRole(string $role): void
    {
        $role = strtoupper($role);
        if ($role === static::DEFAULT_ROLE) {
            return;
        }

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
        $this->plainApiToken = null;
    }

    public function hasUsername(): bool
    {
        return $this->username !== null;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @internal only here to satisfy the theme interface
     */
    public function getIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getLastLogin(): ?DateTime
    {
        if ($this->lastLogin !== null) {
            // make sure to use the users own timezone
            $this->lastLogin->setTimezone(new \DateTimeZone($this->getTimezone()));
        }

        return $this->lastLogin;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = static::DEFAULT_ROLE;

        return array_values(array_unique($roles));
    }

    public function hasRole($role): bool
    {
        return \in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function setSuperAdmin(bool $isSuper): void
    {
        if (true === $isSuper) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    public function removeRole($role): User
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setUserIdentifier(string $identifier): void
    {
        $this->setUsername($identifier);
    }

    public function setEmail(?string $email): User
    {
        $this->email = $email;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): User
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setPassword($password): User
    {
        $this->password = $password;

        return $this;
    }

    public function setPlainPassword($password): User
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function setLastLogin(\DateTime $time = null): User
    {
        $this->lastLogin = $time;

        return $this;
    }

    public function setConfirmationToken($confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function markPasswordRequested(): void
    {
        $this->setPasswordRequestedAt(new \DateTimeImmutable('now', new \DateTimeZone($this->getTimezone())));
    }

    public function markPasswordResetted(): void
    {
        $this->setConfirmationToken(null);
        $this->setPasswordRequestedAt(null);
    }

    public function setPasswordRequestedAt(?\DateTimeImmutable $date): void
    {
        $this->passwordRequestedAt = $date;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     */
    public function getPasswordRequestedAt(): ?\DateTimeImmutable
    {
        return $this->passwordRequestedAt;
    }

    public function isPasswordRequestNonExpired(int $seconds): bool
    {
        $date = $this->getPasswordRequestedAt();

        if (!($date instanceof \DateTimeInterface)) {
            return false;
        }

        return $date->getTimestamp() + $seconds > time();
    }

    public function setRoles(array $roles): User
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->username !== $user->getUserIdentifier()) {
            return false;
        }

        if ($this->enabled !== $user->isEnabled()) {
            return false;
        }

        return true;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'enabled' => $this->enabled,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }

    public function __unserialize(array $data): void
    {
        if (!\array_key_exists('id', $data)) {
            return;
        }
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->enabled = $data['enabled'];
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('initials')]
    #[Serializer\Groups(['Default'])]
    #[OA\Property(type: 'string')]
    public function getInitials(): string
    {
        $length = 2;

        $name = $this->getDisplayName();
        $initial = '';

        if (filter_var($name, FILTER_VALIDATE_EMAIL)) {
            // turn my.email@gmail.com into "My Email"
            $result = mb_strstr($name, '@', true);
            $name = $result === false ? $name : $result;
            $name = str_replace('.', ' ', $name);
        }

        $words = explode(' ', $name);

        // if name contains single word, use first N character
        if (\count($words) === 1) {
            $initial = $words[0];

            if (mb_strlen($name) >= $length) {
                $initial = mb_substr($name, 0, $length, 'UTF-8');
            }
        } else {
            // otherwise, use initial char from each word
            foreach ($words as $word) {
                $initial .= mb_substr($word, 0, 1, 'UTF-8');
            }
            $initial = mb_substr($initial, 0, $length, 'UTF-8');
        }

        $initial = mb_strtoupper($initial);

        return $initial;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): void
    {
        // @CloudRequired because SAML mapping could include a longer value
        $this->accountNumber = StringHelper::ensureMaxLength($accountNumber, 30);
    }

    public function isSystemAccount(): bool
    {
        return $this->systemAccount;
    }

    public function setSystemAccount(bool $isSystemAccount): void
    {
        $this->systemAccount = $isSystemAccount;
    }

    public function getName(): string
    {
        return $this->getDisplayName();
    }

    public function requiresPasswordReset(): bool
    {
        if (!$this->isInternalUser() || !$this->isEnabled()) {
            return false;
        }

        return $this->getPreferenceValue('__pw_reset__') === '1';
    }

    public function setRequiresPasswordReset(bool $require = true): void
    {
        $this->setPreferenceValue('__pw_reset__', ($require ? '1' : '0'));
    }

    public function hasSeenWizard(string $wizard): bool
    {
        $wizards = $this->getPreferenceValue('__wizards__');

        if (\is_string($wizards)) {
            $wizards = explode(',', $wizards);

            return \in_array($wizard, $wizards);
        }

        return false;
    }

    public function setWizardAsSeen(string $wizard): void
    {
        $wizards = $this->getPreferenceValue('__wizards__');
        $values = [];

        if (\is_string($wizards)) {
            $values = explode(',', $wizards);
        }

        if (\in_array($wizard, $values)) {
            return;
        }

        $values[] = $wizard;
        $this->setPreferenceValue('__wizards__', implode(',', array_filter($values)));
    }

    // --------------- 2 Factor Authentication ---------------

    public function setTotpSecret(?string $secret): void
    {
        $this->totpSecret = $secret;
    }

    public function hasTotpSecret(): bool
    {
        return $this->totpSecret !== null;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpEnabled;
    }

    public function enableTotpAuthentication(): void
    {
        $this->totpEnabled = true;
    }

    public function disableTotpAuthentication(): void
    {
        $this->totpEnabled = false;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
    {
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getWorkHoursMonday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_MONDAY, 0);
    }

    public function getWorkHoursTuesday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_TUESDAY, 0);
    }

    public function getWorkHoursWednesday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_WEDNESDAY, 0);
    }

    public function getWorkHoursThursday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_THURSDAY, 0);
    }

    public function getWorkHoursFriday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_FRIDAY, 0);
    }

    public function getWorkHoursSaturday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_SATURDAY, 0);
    }

    public function getWorkHoursSunday(): int
    {
        return (int) $this->getPreferenceValue(UserPreference::WORK_HOURS_SUNDAY, 0);
    }

    public function getWorkStartingDay(): ?\DateTimeInterface
    {
        $date = $this->getPreferenceValue(UserPreference::WORK_STARTING_DAY);

        if ($date === null) {
            return null;
        }

        try {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d h:i:s', $date . ' 00:00:00', new \DateTimeZone($this->getTimezone()));
        } catch (Exception $e) {
        }

        return ($date instanceof \DateTimeInterface) ? $date : null;
    }

    public function setWorkStartingDay(?\DateTimeInterface $date): void
    {
        $this->setPreferenceValue(UserPreference::WORK_STARTING_DAY, $date?->format('Y-m-d'));
    }

    public function getPublicHolidayGroup(): null|string
    {
        $group = $this->getPreferenceValue(UserPreference::PUBLIC_HOLIDAY_GROUP);

        return $group === null ? $group : (string) $group;
    }

    public function getHolidaysPerYear(): float
    {
        $holidays = $this->getPreferenceValue(UserPreference::HOLIDAYS_PER_YEAR, 0.0);

        return $this->getFormattedHoliday(is_numeric($holidays) ? $holidays : 0.0);
    }

    public function setWorkHoursMonday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_MONDAY, $seconds);
    }

    public function setWorkHoursTuesday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_TUESDAY, $seconds);
    }

    public function setWorkHoursWednesday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_WEDNESDAY, $seconds);
    }

    public function setWorkHoursThursday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_THURSDAY, $seconds);
    }

    public function setWorkHoursFriday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_FRIDAY, $seconds);
    }

    public function setWorkHoursSaturday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_SATURDAY, $seconds);
    }

    public function setWorkHoursSunday(int $seconds): void
    {
        $this->setPreferenceValue(UserPreference::WORK_HOURS_SUNDAY, $seconds);
    }

    public function setPublicHolidayGroup(null|string $group = null): void
    {
        $this->setPreferenceValue(UserPreference::PUBLIC_HOLIDAY_GROUP, $group);
    }

    public function setHolidaysPerYear(?float $holidays): void
    {
        if ($holidays !== null) {
            // makes sure that the number is a multiple of 0.5
            $holidays = $this->getFormattedHoliday($holidays);
        }

        $this->setPreferenceValue(UserPreference::HOLIDAYS_PER_YEAR, $holidays ?? 0.0);
    }

    private function getFormattedHoliday(int|float|string|null $holidays): float
    {
        if (!is_numeric($holidays)) {
            $holidays = 0.0;
        }

        return (float) number_format((round($holidays * 2) / 2), 1);
    }

    public function hasContractSettings(): bool
    {
        return $this->hasWorkHourConfiguration() || $this->getHolidaysPerYear() !== 0.0;
    }

    public function hasWorkHourConfiguration(): bool
    {
        return $this->getWorkHoursMonday() !== 0 ||
            $this->getWorkHoursTuesday() !== 0 ||
            $this->getWorkHoursWednesday() !== 0 ||
            $this->getWorkHoursThursday() !== 0 ||
            $this->getWorkHoursFriday() !== 0 ||
            $this->getWorkHoursSaturday() !== 0 ||
            $this->getWorkHoursSunday() !== 0;
    }

    public function getWorkHoursForDay(\DateTimeInterface $dateTime): int
    {
        return match ($dateTime->format('N')) {
            '1' => $this->getWorkHoursMonday(),
            '2' => $this->getWorkHoursTuesday(),
            '3' => $this->getWorkHoursWednesday(),
            '4' => $this->getWorkHoursThursday(),
            '5' => $this->getWorkHoursFriday(),
            '6' => $this->getWorkHoursSaturday(),
            '7' => $this->getWorkHoursSunday(),
            default => throw new \Exception('Unknown day: ' . $dateTime->format('Y-m-d'))
        };
    }

    public function isWorkDay(\DateTimeInterface $dateTime): bool
    {
        return $this->getWorkHoursForDay($dateTime) > 0;
    }

    public function hasSupervisor(): bool
    {
        return $this->supervisor !== null;
    }

    public function getSupervisor(): ?User
    {
        return $this->supervisor;
    }

    public function setSupervisor(?User $supervisor): void
    {
        $this->supervisor = $supervisor;
    }
}
