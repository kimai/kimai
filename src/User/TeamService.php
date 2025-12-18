<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User;

use App\Entity\Team;
use App\Event\TeamCreateEvent;
use App\Event\TeamCreatePostEvent;
use App\Event\TeamCreatePreEvent;
use App\Event\TeamDeleteEvent;
use App\Event\TeamUpdatePostEvent;
use App\Event\TeamUpdatePreEvent;
use App\Repository\TeamRepository;
use App\Validator\ValidationFailedException;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TeamService
{
    /**
     * @var array<string, int>
     */
    private array $cache = [];

    public function __construct(
        private readonly TeamRepository $repository,
        private readonly ValidatorInterface $validator,
        private readonly EventDispatcherInterface $dispatcher,
    )
    {
    }

    public function findTeamByName(string $name): ?Team
    {
        return $this->repository->findOneBy(['name' => $name]);
    }

    public function countTeams(): int
    {
        if (!\array_key_exists('count', $this->cache)) {
            $this->cache['count'] = $this->repository->count([]);
        }

        return $this->cache['count'];
    }

    public function createNewTeam(string $name): Team
    {
        $team = new Team($name);
        $this->dispatcher->dispatch(new TeamCreateEvent($team));

        return $team;
    }

    public function saveTeam(Team $team): Team
    {
        if(null === $team->getId()) {
            // invalidate cache only on new teams
            return $this->saveNewTeam($team);
        }

        return $this->updateTeam($team);
    }

    private function saveNewTeam(Team $team): Team
    {
        if (null !== $team->getId()) {
            throw new InvalidArgumentException('Cannot create team, already persisted');
        }

        $this->validateTeam($team);

        $this->dispatcher->dispatch(new TeamCreatePreEvent($team));
        $this->repository->saveTeam($team);
        $this->dispatcher->dispatch(new TeamCreatePostEvent($team));

        return $team;
    }

    public function hasTeams(): bool
    {
        return $this->countTeams() > 0;
    }

    private function validateTeam(Team $team): void
    {
        $errors = $this->validator->validate($team);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors);
        }
    }

    private function updateTeam(Team $team): Team
    {
        $this->validateTeam($team);

        $this->dispatcher->dispatch(new TeamUpdatePreEvent($team));
        $this->repository->saveTeam($team);
        $this->dispatcher->dispatch(new TeamUpdatePostEvent($team));

        return $team;
    }

    public function deleteTeam(Team $delete): void
    {
        $this->dispatcher->dispatch(new TeamDeleteEvent($delete));
        $this->repository->deleteTeam($delete);
    }
}
