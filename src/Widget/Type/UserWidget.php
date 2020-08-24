<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;

interface UserWidget
{
    /**
     * Sets the current user.
     *
     * @param User $user
     */
    public function setUser(User $user): void;
}
