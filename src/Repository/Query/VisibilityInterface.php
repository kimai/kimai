<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

interface VisibilityInterface
{
    public const SHOW_VISIBLE = 1;
    public const SHOW_HIDDEN = 2;
    public const SHOW_BOTH = 3;

    public const ALLOWED_VISIBILITY_STATES = [
        self::SHOW_BOTH,
        self::SHOW_VISIBLE,
        self::SHOW_HIDDEN,
    ];

    public function getVisibility(): int;

    /**
     * @param int $visibility
     * @return mixed
     */
    public function setVisibility($visibility);
}
