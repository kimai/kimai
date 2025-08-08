<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\ICSCalendarSourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_ics_calendar_sources')]
#[ORM\Entity(repositoryClass: ICSCalendarSourceRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class ICSCalendarSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'icsCalendarSources')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Serializer\Expose]
    #[Serializer\Groups(['User_Entity'])]
    #[OA\Property(ref: '#/components/schemas/User')]
    private ?User $user = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $name = null;

    #[ORM\Column(name: 'url', type: Types::TEXT, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $url = null;

    #[ORM\Column(name: 'color', type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Length(max: 7)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $color = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $enabled = true;

    #[ORM\Column(name: 'last_sync', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?\DateTime $lastSync = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): ICSCalendarSource
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): ICSCalendarSource
    {
        $this->name = $name;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): ICSCalendarSource
    {
        $this->url = $url;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): ICSCalendarSource
    {
        $this->color = $color;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): ICSCalendarSource
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getLastSync(): ?\DateTime
    {
        return $this->lastSync;
    }

    public function setLastSync(?\DateTime $lastSync): ICSCalendarSource
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'ICS Calendar';
    }
} 