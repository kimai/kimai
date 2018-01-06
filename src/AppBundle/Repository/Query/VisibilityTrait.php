<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Repository\Query;

/**
 * Can be used for advanced queries with the: UserRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
trait VisibilityTrait
{
    protected $visibility = self::SHOW_VISIBLE;

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     * @return ProjectQuery
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }
}
