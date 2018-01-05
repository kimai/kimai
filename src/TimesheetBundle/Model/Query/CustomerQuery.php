<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Model\Query;

/**
 * Can be used for advanced queries with the: CustomerRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerQuery extends BaseQuery
{
    const SHOW_VISIBLE = 1;
    const SHOW_HIDDEN = 0;
    const SHOW_BOTH = 2;

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
     * @return CustomerQuery
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }
}
