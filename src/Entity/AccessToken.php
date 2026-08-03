<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_access_token')]
#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\UniqueConstraint(columns: ['token'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(fields: ['token'])]
class AccessToken
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private User $user;
    #[ORM\Column(name: 'token', type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $token;
    #[ORM\Column(name: 'name', type: Types::STRING, length: 50, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $name = null;
    #[ORM\Column(name: 'last_usage', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastUsage = null;
    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;
    /**
     * The list of granted API scopes (e.g. "timesheet:read").
     *
     * A value of NULL means "legacy token" with full access (backward compatibility):
     * tokens created before this feature existed are not restricted at runtime.
     * An explicit (even empty) array activates the scope restriction.
     *
     * @var array<string>|null
     */
    #[ORM\Column(name: 'scopes', type: Types::JSON, nullable: true)]
    private ?array $scopes = null;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setLastUsage(\DateTimeImmutable $lastUsage): void
    {
        $this->lastUsage = $lastUsage;
    }

    public function getLastUsage(): ?\DateTimeImmutable
    {
        return $this->lastUsage;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt > new \DateTimeImmutable();
    }

    /**
     * @return array<string>|null
     */
    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    /**
     * @param array<string>|null $scopes
     */
    public function setScopes(?array $scopes): void
    {
        if ($scopes !== null) {
            // normalize: unique, re-indexed list of non-empty strings
            $scopes = array_values(array_unique(array_filter($scopes, static fn (string $scope) => $scope !== '')));
        }

        $this->scopes = $scopes;
    }

    /**
     * A legacy token has no configured scopes and therefore runs with the
     * full permissions of its user (backward compatibility).
     */
    public function isLegacy(): bool
    {
        return $this->scopes === null;
    }

    public function hasScope(string $scope): bool
    {
        // legacy tokens are allowed to do everything
        if ($this->scopes === null) {
            return true;
        }

        return \in_array($scope, $this->scopes, true);
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
