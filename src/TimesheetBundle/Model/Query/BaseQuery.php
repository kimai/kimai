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
 * Base class for advanced Repository queries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class BaseQuery
{

    const DEFAULT_PAGESIZE = 25;
    const DEFAULT_PAGE = 1;

    /**
     * @var int
     */
    protected $page = self::DEFAULT_PAGE;
    /**
     * @var int
     */
    protected $pageSize = self::DEFAULT_PAGESIZE;
    /**
     * @var string
     */
    protected $orderBy = 'id';

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return BaseQuery
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return BaseQuery
     */
    public function setPageSize($pageSize)
    {
        if (!empty($pageSize) && (int)$pageSize > 0) {
            $this->pageSize = (int)$pageSize;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * You need to validate carefully if this value is used from a user-input.
     *
     * @param string $orderBy
     * @return BaseQuery
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }
}
