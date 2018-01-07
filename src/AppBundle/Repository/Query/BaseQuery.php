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
 * Base class for advanced Repository queries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class BaseQuery
{

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    const DEFAULT_PAGESIZE = 25;
    const DEFAULT_PAGE = 1;

    const RESULT_TYPE_PAGER = 'PagerFanta';
    const RESULT_TYPE_QUERYBUILDER = 'QueryBuilder';

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
     * @var string
     */
    protected $order = 'ASC';
    /**
     * @var string
     */
    protected $resultType = self::RESULT_TYPE_PAGER;

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     * @return $this
     */
    public function setOrder($order)
    {
        if (in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
            $this->order = $order;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getResultType()
    {
        return $this->resultType;
    }

    /**
     * @param string $resultType
     * @return $this
     */
    public function setResultType($resultType)
    {
        if (in_array($resultType, [self::RESULT_TYPE_PAGER, self::RESULT_TYPE_QUERYBUILDER])) {
            $this->resultType = $resultType;
        }
        return $this;
    }
}
