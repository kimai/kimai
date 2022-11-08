<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait CommentTableTypeTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(name: 'message', type: 'text', nullable: false)]
    #[Assert\NotNull]
    private ?string $message = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    #[Assert\NotNull]
    private ?User $createdBy = null;
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Assert\NotNull]
    private ?\DateTime $createdAt = null;
    #[ORM\Column(name: 'pinned', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Assert\NotNull]
    private bool $pinned = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned)
    {
        $this->pinned = $pinned;
    }
}
