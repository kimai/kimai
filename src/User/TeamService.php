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
use App\Event\TeamUpdatePostEvent;
use App\Event\TeamUpdatePreEvent;
use App\Validator\ValidationFailedException;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Repository\TeamRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;

final class TeamService
{
    /**
     * @var array<string, int>
     */
    private array $cache = [];

    public function __construct(
        private TeamRepository $repository,
        private readonly ValidatorInterface $validator,
        private readonly EventDispatcherInterface $dispatcher,
    )
    {
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

    /**
     *
     * @param Team $team
     * @return Team
     */
    private function saveNewTeam(Team $team): Team
    {
        if (null !== $team->getId()) {
            throw new InvalidArgumentException('Cannot create team, already persisted');
        }

        $this->validateTeam($team, ['TeamCreate']);

        $this->dispatcher->dispatch(new TeamCreatePreEvent($team));
        $this->repository->saveTeam($team);
        $this->dispatcher->dispatch(new TeamCreatePostEvent($team));

        return $team;
    }

    public function hasTeams(): bool
    {
        return $this->countTeams() > 0;
    }

    private function validateTeam(Team $team, array $groups = []):void
    {
        $errors = $this->validator->validate($team, null, $groups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors);
        }
    }

    private function updateTeam(Team $team, array $groups = []): Team
    {
        $this->validateTeam($team, $groups);

        $this->dispatcher->dispatch(new TeamUpdatePreEvent($team));
        $this->repository->saveTeam($team);
        $this->dispatcher->dispatch(new TeamUpdatePostEvent($team));

        return $team;
    }

    public function deleteTeam(Team $delete): void
    {
        $this->dispatcher->dispatch(new TeamDeletePreEvent($delete));
        $this->repository->deleteTeam($delete);
        $this->dispatcher->dispatch(new TeamDeletePostEvent($delete));
    }
}
