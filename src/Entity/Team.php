<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Validator\Constraints as Constraints;
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
 * @Constraints\Team
 */
class Team
{
    /**
     * @var int|null
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
     * All team member (including team leads)
     *
     * @var TeamMember[]|Collection<TeamMember>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/TeamMember"))
     *
     * @ORM\OneToMany(targetEntity="App\Entity\TeamMember", mappedBy="team", fetch="LAZY", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\Count(min="1")
     */
    private $members;
    /**
     * Customers assigned to the team
     *
     * @var Collection<Customer>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Customer"))
     *
     * @ORM\ManyToMany(targetEntity="Customer", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $customers;
    /**
     * Projects assigned to the team
     *
     * @var Collection<Project>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity", "Expanded"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Project"))
     *
     * @ORM\ManyToMany(targetEntity="Project", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $projects;
    /**
     * Activities assigned to the team
     *
     * @var Collection<Activity>
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Team_Entity", "Expanded"})
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Activity"))
     *
     * @ORM\ManyToMany(targetEntity="Activity", mappedBy="teams", fetch="EXTRA_LAZY")
     */
    private $activities;

    use ColorTrait;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->customers = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->activities = new ArrayCollection();
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

    /**
     * Indexed by ID to use it within collection type forms.
     *
     * @return TeamMember[]
     */
    public function getMembers(): iterable
    {
        $all = [];
        foreach ($this->members as $member) {
            if ($member->getId() === null) {
                $all[] = $member;
            } else {
                $all[$member->getId()] = $member;
            }
        }

        return $all;
    }

    public function addMember(TeamMember $member): void
    {
        if ($this->members->contains($member)) {
            return;
        }

        if ($member->getTeam() === null) {
            $member->setTeam($this);
        }

        if ($member->getTeam() !== $this) {
            throw new \InvalidArgumentException('Cannot set foreign team membership');
        }

        if (null !== ($existing = $this->findMember($member))) {
            $existing->setTeamlead($member->isTeamlead());

            return;
        }

        // when using the API an invalid user id does not trigger the validation first, but after calling this method :-(
        if ($member->getUser() !== null) {
            $this->members->add($member);
            $member->getUser()->addMembership($member);
        }
    }

    public function hasMember(TeamMember $member): bool
    {
        return $this->members->contains($member);
    }

    private function findMember(TeamMember $member): ?TeamMember
    {
        foreach ($this->members as $oldMember) {
            if ($oldMember->getUser() === $member->getUser() && $oldMember->getTeam() === $member->getTeam()) {
                return $oldMember;
            }
        }

        return null;
    }

    public function removeMember(TeamMember $member): void
    {
        $existingMember = $this->findMember($member);
        if ($existingMember === null) {
            return;
        }

        $this->members->removeElement($existingMember);
        $existingMember->getUser()->removeMembership($existingMember);
    }

    /**
     * BE AWARE: this property is deprecated and will be removed with 2.0 - teams can have multiple teamleads since 1.15!
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("teamlead"),
     * @Serializer\Groups({"Team_Entity"})
     * @SWG\Property(ref="#/definitions/User")
     *
     * @deprecated since 1.15 - will be removed with 2.0
     * @return User|null
     */
    public function getTeamlead(): ?User
    {
        foreach ($this->members as $member) {
            if ($member->isTeamlead()) {
                return $member->getUser();
            }
        }

        return null;
    }

    /**
     * @return User[]
     */
    public function getTeamleads(): array
    {
        $leads = [];
        foreach ($this->members as $member) {
            if ($member->isTeamlead()) {
                $leads[] = $member->getUser();
            }
        }

        return $leads;
    }

    public function isTeamlead(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($user === $member->getUser()) {
                return $member->isTeamlead();
            }
        }

        return false;
    }

    /**
     * @deprecated since 1.15 - will be removed with 2.0
     * @param User $teamlead
     */
    public function setTeamlead(User $teamlead): void
    {
        $this->addTeamlead($teamlead);
    }

    public function addTeamlead(User $user): void
    {
        $this->addUser($user, true);
    }

    /**
     * Will only remove the teamlead flag, not the user from the team.
     *
     * @param User $user
     */
    public function removeTeamlead(User $user): void
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                $member->setTeamlead(false);

                return;
            }
        }
    }

    public function hasUser(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    public function hasUsers(): bool
    {
        return !$this->members->isEmpty();
    }

    public function hasTeamleads(): bool
    {
        foreach ($this->members as $member) {
            if ($member->isTeamlead()) {
                return true;
            }
        }

        return false;
    }

    public function addUser(User $user, bool $teamlead = false): void
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                $member->setTeamlead($teamlead);

                return;
            }
        }

        $member = new TeamMember();
        $member->setTeam($this);
        $member->setUser($user);
        $member->setTeamlead($teamlead);

        $this->addMember($member);
    }

    public function removeUser(User $user): void
    {
        $memberToRemove = null;
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                $memberToRemove = $member;
                break;
            }
        }

        if ($memberToRemove === null) {
            return;
        }

        $this->removeMember($memberToRemove);
    }

    /**
     * Returns all users in the team, both teamlead and normal member.
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("users"),
     * @Serializer\Groups({"Team_Entity"})
     * @SWG\Property(ref="#/definitions/User")
     *
     * @return User[]
     */
    public function getUsers(): array
    {
        $users = [];
        foreach ($this->members as $member) {
            $users[] = $member->getUser();
        }

        return $users;
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

    public function hasActivity(Activity $activity): bool
    {
        return $this->activities->contains($activity);
    }

    public function addActivity(Activity $activity)
    {
        if ($this->activities->contains($activity)) {
            return;
        }

        $this->activities->add($activity);
        $activity->addTeam($this);
    }

    public function removeActivity(Activity $activity)
    {
        if (!$this->activities->contains($activity)) {
            return;
        }

        $this->activities->removeElement($activity);
        $activity->removeTeam($this);
    }

    /**
     * @return Collection<Activity>
     */
    public function getActivities(): iterable
    {
        return $this->activities;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    public function __clone()
    {
        if ($this->id !== null) {
            $this->id = null;
        }

        $members = $this->members;
        $this->members = new ArrayCollection();
        foreach ($members as $member) {
            $newMember = clone $member;
            $newMember->setTeam($this);
            $this->addMember($newMember);
        }

        $customers = $this->customers;
        $this->customers = new ArrayCollection();
        foreach ($customers as $customer) {
            $this->addCustomer($customer);
        }

        $projects = $this->projects;
        $this->projects = new ArrayCollection();
        foreach ($projects as $project) {
            $this->addProject($project);
        }

        $activities = $this->activities;
        $this->activities = new ArrayCollection();
        foreach ($activities as $activity) {
            $this->addActivity($activity);
        }
    }
}
