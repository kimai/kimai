<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\User;

final class TagFormTypeQuery
{
    /**
     * @var User
     */
    private $user;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): TagFormTypeQuery
    {
        $this->user = $user;

        return $this;
    }
}
