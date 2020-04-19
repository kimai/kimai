<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

trait VisibilityTrait
{
    /**
     * @var int
     */
    private $visibility = VisibilityInterface::SHOW_VISIBLE;

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility($visibility)
    {
        $visibility = (int) $visibility;
        if (\in_array($visibility, VisibilityInterface::ALLOWED_VISIBILITY_STATES, true)) {
            $this->visibility = $visibility;
        }

        return $this;
    }
}
