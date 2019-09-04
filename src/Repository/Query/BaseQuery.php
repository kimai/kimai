<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Team;
use App\Entity\User;
use App\Utils\SearchTerm;

/**
 * Base class for advanced Repository queries.
 */
class BaseQuery
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public const DEFAULT_PAGESIZE = 50;
    public const DEFAULT_PAGE = 1;

    public const RESULT_TYPE_OBJECTS = 'Objects';
    public const RESULT_TYPE_PAGER = 'PagerFanta';
    public const RESULT_TYPE_QUERYBUILDER = 'QueryBuilder';

    /**
     * @var int
     */
    private $page = self::DEFAULT_PAGE;
    /**
     * @var int
     */
    private $pageSize = self::DEFAULT_PAGESIZE;
    /**
     * @var string
     */
    private $orderBy = 'id';
    /**
     * @var string
     */
    private $order = self::ORDER_ASC;
    /**
     * @var string
     */
    private $resultType = self::RESULT_TYPE_PAGER;
    /**
     * @var User
     */
    private $currentUser;
    /**
     * @var Team[]
     */
    private $teams = [];
    /**
     * @var SearchTerm|null
     */
    private $searchTerm;

    public function addTeam(Team $team): self
    {
        $this->teams[$team->getId()] = $team;

        return $this;
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return array_values($this->teams);
    }

    public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setCurrentUser(User $user)
    {
        $this->currentUser = $user;

        return $this;
    }

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
        $this->page = (int) $page;

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        if ($pageSize !== null && (int) $pageSize > 0) {
            $this->pageSize = (int) $pageSize;
        }

        return $this;
    }

    public function getOrderBy(): string
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

    public function getOrder(): string
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
     * @deprecated since 1.0
     * @return string
     */
    public function getResultType()
    {
        return $this->resultType;
    }

    /**
     * @deprecated since 1.0
     * @param string $resultType
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setResultType(string $resultType)
    {
        $allowed = [self::RESULT_TYPE_PAGER, self::RESULT_TYPE_QUERYBUILDER, self::RESULT_TYPE_OBJECTS];

        if (!in_array($resultType, $allowed)) {
            throw new \InvalidArgumentException('Unsupported query result type');
        }

        $this->resultType = $resultType;

        return $this;
    }

    public function hasSearchTerm(): bool
    {
        return null !== $this->searchTerm;
    }

    public function getSearchTerm(): ?SearchTerm
    {
        return $this->searchTerm;
    }

    /**
     * @param SearchTerm|null $searchTerm
     * @return BaseQuery
     */
    public function setSearchTerm(?SearchTerm $searchTerm)
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    /**
     * Returns whether the query has changed fields, compared to the original state.
     *
     * @return bool
     */
    public function isDirty(): bool
    {
        if ($this->page !== self::DEFAULT_PAGE) {
            return true;
        }

        if ($this->pageSize !== self::DEFAULT_PAGESIZE) {
            return true;
        }

        if (!empty($this->teams)) {
            return true;
        }

        if (null !== $this->searchTerm) {
            return true;
        }

        return false;
    }
}
