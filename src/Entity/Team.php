<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="kimai2_teams",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TeamRepository")
 * @UniqueEntity("name")
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\AccessorOrder("custom", custom = {"id", "name", "teamlead", "users", "customers", "projects"})
 */
class Team
{
    /**
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * Team name
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=100, allowEmptyString=false)
     */
    private $name;
    /**
     * Teamlead
     *
     * The teamlead for this team
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     *
     * @var User
     *
     * @SWG\Property(ref="#/definitions/User")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @Assert\NotNull()
     */
    private $teamlead;
    /**
     * Team member
     *
     * All team member, including the teamlead
     *
     * @var User[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     *
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/User"))
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $users;
    /**
     * Customers
     *
     * All customers assigned to the team
     *
     * @var Customer[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     *
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Customer"))
     *
     * @ORM\ManyToMany(targetEntity="Customer", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $customers;
    /**
     * Projects
     *
     * All projects assigned to the team
     *
     * @var Project[]|ArrayCollection
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     *
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Project"))
     *
     * @ORM\ManyToMany(targetEntity="Project", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $projects;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->customers = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): Team
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getTeamLead(): ?User
    {
        return $this->teamlead;
    }

    public function isTeamlead(User $user): bool
    {
        return $this->teamlead === $user;
    }

    public function setTeamLead(User $teamlead): Team
    {
        $this->teamlead = $teamlead;
        $this->addUser($teamlead);

        return $this;
    }

    public function hasUser(User $user): bool
    {
        return $this->users->contains($user);
    }

    public function addUser(User $user)
    {
        if ($this->users->contains($user)) {
            return;
        }

        $this->users->add($user);
        $user->addTeam($this);
    }

    public function removeUser(User $user)
    {
        if (!$this->users->contains($user)) {
            return;
        }

        $this->users->removeElement($user);
        $user->removeTeam($this);
    }

    /**
     * @return Collection<User>
     */
    public function getUsers(): iterable
    {
        return $this->users;
    }

    public function hasCustomer(Customer $customer): bool
    {
        return $this->customers->contains($customer);
    }

    public function addCustomer(Customer $customer)
    {
        if ($this->customers->contains($customer)) {
            return;
        }

        $this->customers->add($customer);
        $customer->addTeam($this);
    }

    public function removeCustomer(Customer $customer)
    {
        if (!$this->customers->contains($customer)) {
            return;
        }

        $this->customers->removeElement($customer);
        $customer->removeTeam($this);
    }

    /**
     * @return Collection<Customer>
     */
    public function getCustomers(): iterable
    {
        return $this->customers;
    }

    public function hasProject(Project $project): bool
    {
        return $this->projects->contains($project);
    }

    public function addProject(Project $project)
    {
        if ($this->projects->contains($project)) {
            return;
        }

        $this->projects->add($project);
        $project->addTeam($this);
    }

    public function removeProject(Project $project)
    {
        if (!$this->projects->contains($project)) {
            return;
        }

        $this->projects->removeElement($project);
        $project->removeTeam($this);
    }

    /**
     * @return Collection<Project>
     */
    public function getProjects(): iterable
    {
        return $this->projects;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
