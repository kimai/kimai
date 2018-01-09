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
interface VisibilityInterface
{
    const SHOW_VISIBLE = 1;
    const SHOW_HIDDEN = 0;
    const SHOW_BOTH = 2;

    /**
     * @return $this
     */
    public function getVisibility();

    /**
     * @param int $visibility
     * @return $this
     */
    public function setVisibility($visibility);

    /**
     * @return bool
     */
    public function isExclusiveVisibility();

    /**
     * @param bool $exclusiveVisibility
     * @return $this
     */
    public function setExclusiveVisibility($exclusiveVisibility);
}
