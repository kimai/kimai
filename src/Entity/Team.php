<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\TeamRepository;
use App\Validator\Constraints as Constraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_teams')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('name')]
#[Serializer\ExclusionPolicy('all')]
#[Constraints\Team]
class Team
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $id = null;
    /**
     * Team name
     */
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $name = null;
    /**
     * All team member (including team leads)
     *
     * @var Collection<TeamMember>
     */
    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamMember::class, cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\Count(min: 1)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Team_Entity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/TeamMember'))]
    private Collection $members;
    /**
     * Customers assigned to the team
     *
     * @var Collection<Customer>
     */
    #[ORM\ManyToMany(targetEntity: Customer::class, mappedBy: 'teams', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Team_Entity'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Customer'))]
    private Collection $customers;
    /**
     * Projects assigned to the team
     *
     * @var Collection<Project>
     */
    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'teams', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Team_Entity', 'Expanded'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Project'))]
    private Collection $projects;
    /**
     * Activities assigned to the team
     *
     * @var Collection<Activity>
     */
    #[ORM\ManyToMany(targetEntity: Activity::class, mappedBy: 'teams', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Team_Entity', 'Expanded'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/Activity'))]
    private Collection $activities;

    use ColorTrait;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->members = new ArrayCollection();
        $this->customers = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): Team
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return Collection<TeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
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

        // when using the API an invalid User ID triggers the validation too late
        if (($user = $member->getUser()) === null) {
            return;
        }

        if (null !== $this->findMemberByUser($user)) {
            return;
        }

        $this->members->add($member);
        $user->addMembership($member);
    }

    public function hasMember(TeamMember $member): bool
    {
        return $this->members->contains($member);
    }

    private function findMemberByUser(User $user): ?TeamMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser() === $user) {
                return $member;
            }
        }

        return null;
    }

    public function removeMember(TeamMember $member): void
    {
        if (!$this->members->contains($member)) {
            return;
        }

        $this->members->removeElement($member);
        $member->getUser()->removeMembership($member);
        $member->setTeam(null);
        $member->setUser(null);
    }

    /**
     * @return list<User>
     */
    public function getTeamleads(): array
    {
        $leads = [];
        foreach ($this->members as $member) {
            if ($member->isTeamlead() && $member->getUser() !== null) {
                $leads[] = $member->getUser();
            }
        }

        return $leads;
    }

    public function isTeamlead(User $user): bool
    {
        if (null !== ($member = $this->findMemberByUser($user))) {
            return $member->isTeamlead();
        }

        return false;
    }

    public function addTeamlead(User $user): void
    {
        if (null !== ($member = $this->findMemberByUser($user))) {
            $member->setTeamlead(true);

            return;
        }

        $member = new TeamMember();
        $member->setTeam($this);
        $member->setUser($user);
        $member->setTeamlead(true);

        $this->addMember($member);
    }

    /**
     * Removes the teamlead flag, but leaves the user within the team.
     *
     * @param User $user
     */
    public function demoteTeamlead(User $user): void
    {
        if (null !== ($member = $this->findMemberByUser($user))) {
            $member->setTeamlead(false);
        }
    }

    public function hasUser(User $user): bool
    {
        return (null !== $this->findMemberByUser($user));
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

    public function addUser(User $user): void
    {
        if (null !== $this->findMemberByUser($user)) {
            return;
        }

        $member = new TeamMember();
        $member->setTeam($this);
        $member->setUser($user);

        $this->addMember($member);
    }

    public function removeUser(User $user): void
    {
        if (null !== ($member = $this->findMemberByUser($user))) {
            $this->removeMember($member);
        }
    }

    /**
     * Returns all users in the team, both teamlead and normal member.
     *
     * @return list<User>
     */
    public function getUsers(): array
    {
        $users = [];
        foreach ($this->members as $member) {
            if ($member->getUser() !== null) {
                $users[] = $member->getUser();
            }
        }

        return $users;
    }

    public function hasCustomer(Customer $customer): bool
    {
        return $this->customers->contains($customer);
    }

    public function addCustomer(Customer $customer): void
    {
        if ($this->customers->contains($customer)) {
            return;
        }

        $this->customers->add($customer);
        $customer->addTeam($this);
    }

    public function removeCustomer(Customer $customer): void
    {
        if (!$this->customers->contains($customer)) {
            return;
        }

        $this->customers->removeElement($customer);
        $customer->removeTeam($this);
    }

    /**
     * @internal
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

    public function addProject(Project $project): void
    {
        if ($this->projects->contains($project)) {
            return;
        }

        $this->projects->add($project);
        $project->addTeam($this);
    }

    public function removeProject(Project $project): void
    {
        if (!$this->projects->contains($project)) {
            return;
        }

        $this->projects->removeElement($project);
        $project->removeTeam($this);
    }

    /**
     * @internal
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

    public function addActivity(Activity $activity): void
    {
        if ($this->activities->contains($activity)) {
            return;
        }

        $this->activities->add($activity);
        $activity->addTeam($this);
    }

    public function removeActivity(Activity $activity): void
    {
        if (!$this->activities->contains($activity)) {
            return;
        }

        $this->activities->removeElement($activity);
        $activity->removeTeam($this);
    }

    /**
     * @internal
     * @return Collection<Activity>
     */
    public function getActivities(): iterable
    {
        return $this->activities;
    }

    public function __toString(): string
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
