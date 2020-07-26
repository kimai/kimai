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
    private $includeUsers = [];

    /**
     * Sets a list of users which must be included in the result always.
     *
     * @param array $users
     * @return UserFormTypeQuery
     */
    public function setUsersAlwaysIncluded(array $users): UserFormTypeQuery
    {
        $this->includeUsers = $users;

        return $this;
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
}
