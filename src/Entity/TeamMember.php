<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_users_teams",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"user_id", "team_id"})
 *      }
 * )
 * @ORM\Entity
 *
 * @Serializer\ExclusionPolicy("all")
 */
class TeamMember
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var User
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "Entity", "Team_Entity"})
     * @SWG\Property(ref="#/definitions/User")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="memberships")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $user;
    /**
     * @var Team
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "Entity", "User_Entity"})
     * @SWG\Property(ref="#/definitions/Team")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Team", inversedBy="members")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $team;
    /**
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default", "Entity", "Team_Entity", "User_Entity"})
     *
     * @ORM\Column(name="teamlead", type="boolean", nullable=false, options={"default": false})
     * @Assert\NotNull()
     */
    private $teamlead = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isTeamlead(): bool
    {
        return $this->teamlead;
    }

    public function setTeamlead(bool $teamlead): void
    {
        $this->teamlead = $teamlead;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): void
    {
        $this->team = $team;
    }

    public function __clone()
    {
        if ($this->id !== null) {
            $id = null;
        }
    }
}
