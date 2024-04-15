<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_access_token')]
#[ORM\Entity(repositoryClass: 'App\Repository\AccessTokenRepository')]
#[ORM\UniqueConstraint(columns: ['token'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(fields: ['token'])]
class AccessToken
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private User $user;
    #[ORM\Column(name: 'token', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $token;
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $name = null;
    #[ORM\Column(name: 'last_usage', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsage = null;
    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

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

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
