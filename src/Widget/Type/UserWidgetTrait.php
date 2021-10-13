<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;

/**
 * Needs to be used on a SimpleWidget
 * @internal
 */
trait UserWidgetTrait
{
    public function setUser(User $user): void
    {
        $this->setOption('user', $user);
    }
}
