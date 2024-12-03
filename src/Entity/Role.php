<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_roles')]
#[ORM\UniqueConstraint(name: 'roles_name', columns: ['name'])]
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
class Role
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 50)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Role
    {
        $this->name = strtoupper($name);

        return $this;
    }

    public function isUser(): bool
    {
        return $this->name === User::ROLE_USER;
    }
}
