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

    public const USER_ORDER_ALLOWED = ['username', 'alias', 'title', 'email', 'systemAccount'];

    private ?string $role = null;
    /**
     * @var Team[]
     */
    private array $searchTeams = [];
    private ?bool $systemAccount = null;
    /**
     * @var array<int>
     */
    private array $userIds = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'username',
            'searchTeams' => [],
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
            'systemAccount' => null,
            'userIds' => [],
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

    public function getSystemAccount(): ?bool
    {
        return $this->systemAccount;
    }

    public function setSystemAccount(?bool $systemAccount): void
    {
        $this->systemAccount = $systemAccount;
    }

    /**
     * @param array<int> $ids
     */
    public function setUserIds(array $ids): void
    {
        $this->userIds = $ids;
    }

    /**
     * @return int[]
     */
    public function getUserIds(): array
    {
        return $this->userIds;
    }
}
