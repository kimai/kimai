<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\RolePermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_roles_permissions')]
#[ORM\UniqueConstraint(name: 'role_permission', columns: ['role_id', 'permission'])]
#[ORM\Entity(repositoryClass: RolePermissionRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity(['role', 'permission'])]
class RolePermission
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Role $role = null;
    #[ORM\Column(name: 'permission', type: 'string', length: 50, nullable: false)]
    #[Assert\Length(max: 50)]
    private ?string $permission = null;
    #[ORM\Column(name: 'allowed', type: 'boolean', nullable: false, options: ['default' => false])]
    #[Assert\NotNull]
    private bool $allowed = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): RolePermission
    {
        $this->role = $role;

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): RolePermission
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Alias for isValue()
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function setAllowed(bool $allowed): RolePermission
    {
        $this->allowed = $allowed;

        return $this;
    }
}
