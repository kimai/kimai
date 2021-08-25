<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Team;

/**
 * Can be used for advanced queries with the: UserRepository
 */
class UserQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;

    public const USER_ORDER_ALLOWED = ['id', 'alias', 'username', 'title', 'email'];

    /**
     * @var string|null
     */
    private $role;
    /**
     * @var Team[]
     */
    private $searchTeams = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'username',
            'searchTeams' => [],
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
        ]);
    }

    /**
     * @return Team[]
     */
    public function getSearchTeams(): array
    {
        return $this->searchTeams;
    }

    /**
     * @param Team[] $searchTeams
     */
    public function setSearchTeams(array $searchTeams): void
    {
        $this->searchTeams = $searchTeams;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): UserQuery
    {
        $this->role = $role;

        return $this;
    }
}
