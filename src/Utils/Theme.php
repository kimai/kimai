<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\User;

final class Theme
{
    public function getUserColor(User $user): string
    {
        $color = $user->getColor();

        if ($color !== null) {
            return $color;
        }

        return (new Color())->getRandom($user->getDisplayName());
    }

    public function getColor(?string $color, ?string $identifier = null): string
    {
        if ($color !== null) {
            return $color;
        }

        return (new Color())->getRandom($identifier);
    }
}
