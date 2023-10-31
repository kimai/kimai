<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\WorkingTimeRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_working_times')]
#[ORM\UniqueConstraint(columns: ['user_id', 'date'])]
#[ORM\Entity(repositoryClass: WorkingTimeRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Serializer\ExclusionPolicy('all')]
class WorkingTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;
    #[ORM\Column(name: 'date', type: 'date', nullable: false)]
    #[Assert\NotNull]
    private \DateTimeInterface $date;
    #[ORM\Column(name: 'expected', type: 'integer', nullable: false)]
    #[Assert\NotNull]
    private int $expectedTime = 0;
    #[ORM\Column(name: 'actual', type: 'integer', nullable: false)]
    #[Assert\NotNull]
    private int $actualTime = 0;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', nullable: true, onDelete: 'SET NULL')]
    private ?User $approvedBy = null;
    #[ORM\Column(name: 'approved_at', type: 'datetime_immutable', nullable: true)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct(User $user, \DateTimeInterface $date)
    {
        $this->user = $user;
        $this->date = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getExpectedTime(): int
    {
        return $this->expectedTime;
    }

    public function setExpectedTime(int $expectedTime): void
    {
        $this->expectedTime = $expectedTime;
    }

    public function getActualTime(): int
    {
        return $this->actualTime;
    }

    public function setActualTime(int $actualTime): void
    {
        $this->actualTime = $actualTime;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): void
    {
        $this->approvedBy = $approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }

    public function isApproved(): bool
    {
        return $this->approvedAt !== null;
    }
}
