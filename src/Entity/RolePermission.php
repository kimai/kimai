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

/**
 * @ORM\Table(name="kimai2_roles_permissions",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="role_permission", columns={"role_id","permission"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\RolePermissionRepository")
 * @UniqueEntity({"role", "permission"})
 */
class RolePermission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Role")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $role;
    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private $permission;
    /**
     * @var bool
     *
     * @ORM\Column(name="value", type="boolean", nullable=false, options={"default": false})
     * @Assert\NotNull()
     */
    private $value = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): RolePermission
    {
        $this->id = $id;

        return $this;
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

    public function isValue(): bool
    {
        return $this->value;
    }

    /**
     * Alias for isValue() 
     */
    public function isAllowed(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): RolePermission
    {
        $this->value = $value;

        return $this;
    }
}
