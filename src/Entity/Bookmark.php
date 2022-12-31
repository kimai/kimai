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

#[ORM\Table(name: 'kimai2_bookmarks')]
#[ORM\UniqueConstraint(columns: ['user_id', 'name'])]
#[ORM\Entity(repositoryClass: 'App\Repository\BookmarkRepository')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(fields: ['user', 'name'])]
class Bookmark
{
    public const SEARCH_DEFAULT = 'search-default';
    public const COLUMN_VISIBILITY = 'columns';
    public const TIMESHEET = 'timesheet';

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: false)]
    #[Assert\NotNull]
    private ?User $user = null;
    #[ORM\Column(name: 'type', type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 20)]
    private ?string $type = null;
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $name = null;
    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setContent(array $content): void
    {
        $this->content = json_encode($content);
    }

    public function getContent(): array
    {
        if ($this->content === null) {
            return [];
        }

        return json_decode($this->content, true);
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
