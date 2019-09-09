<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

/**
 * Query class for Repositories with a visibility field.
 */
class VisibilityQuery extends BaseQuery
{
    public const SHOW_VISIBLE = 1;
    public const SHOW_HIDDEN = 2;
    public const SHOW_BOTH = 3;

    public const ALLOWED_VISIBILITY_STATES = [
        self::SHOW_BOTH,
        self::SHOW_VISIBLE,
        self::SHOW_HIDDEN,
    ];

    /**
     * @var int
     */
    private $visibility = self::SHOW_VISIBLE;

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        if (!is_int($visibility) && $visibility != (int) $visibility) {
            return $this;
        }

        $visibility = (int) $visibility;
        if (in_array($visibility, self::ALLOWED_VISIBILITY_STATES, true)) {
            $this->visibility = $visibility;
        }

        return $this;
    }
}
