<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\User;

/**
 * Can be used to pre-fill form types with: UserRepository::getQueryBuilderForFormType()
 */
final class UserFormTypeQuery extends BaseFormTypeQuery
{
    use VisibilityTrait;

    /**
     * @var User[]
     */
    private array $includeUsers = [];
    /**
     * @var User[]
     */
    private array $ignoredUsers = [];

    /**
     * Sets a list of users which must be included in the result always.
     *
     * @param array<User> $users
     */
    public function setUsersAlwaysIncluded(array $users): void
    {
        $this->includeUsers = $users;
    }

    /**
     * Get the list of users which should always be included in the result.
     *
     * @return User[]
     */
    public function getUsersAlwaysIncluded(): array
    {
        return $this->includeUsers;
    }

    /**
     * Given user will be excluded from the result set.
     *
     * @param User $user
     * @return $this
     */
    public function addUserToIgnore(User $user): UserFormTypeQuery
    {
        $this->ignoredUsers[] = $user;

        return $this;
    }

    /**
     * Returns the list of users that should not be loaded.
     *
     * @return User[]
     */
    public function getUsersToIgnore(): array
    {
        return $this->ignoredUsers;
    }
}
