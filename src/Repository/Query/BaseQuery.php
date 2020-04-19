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
use Symfony\Component\Form\FormErrorIterator;

/**
 * Base class for advanced Repository queries.
 */
class BaseQuery
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public const DEFAULT_PAGESIZE = 50;
    public const DEFAULT_PAGE = 1;

    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const RESULT_TYPE_OBJECTS = 'Objects';
    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const RESULT_TYPE_PAGER = 'PagerFanta';
    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const RESULT_TYPE_QUERYBUILDER = 'QueryBuilder';

    private $defaults = [
        'page' => self::DEFAULT_PAGE,
        'pageSize' => self::DEFAULT_PAGESIZE,
        'orderBy' => 'id',
        'order' => self::ORDER_ASC,
        'searchTerm' => null,
    ];
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
     * @deprecated since 1.4, will be removed with 2.0
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

    /**
     * @param Team[] $teams
     * @return $this
     */
    public function setTeams(?array $teams): self
    {
        $this->teams = [];

        if (null !== $teams) {
            foreach ($teams as $team) {
                $this->addTeam($team);
            }
        }

        return $this;
    }

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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setOrder($order)
    {
        if (\in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
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
        @trigger_error('BaseQuery::getResultType() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        return $this->resultType;
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
     * @return self
     */
    public function setSearchTerm(?SearchTerm $searchTerm)
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    protected function set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } elseif (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    /**
     * @param array $defaults
     * @return self
     */
    protected function setDefaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        foreach ($this->defaults as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * @param FormErrorIterator $errors
     * @return self
     */
    public function resetByFormError(FormErrorIterator $errors)
    {
        foreach ($errors as $error) {
            $key = $error->getOrigin()->getName();
            if (\array_key_exists($key, $this->defaults)) {
                $this->set($key, $this->defaults[$key]);
            }
        }

        return $this;
    }

    public function copyTo(BaseQuery $query): BaseQuery
    {
        $query->setDefaults($this->defaults);
        if (null !== $this->getCurrentUser()) {
            $query->setCurrentUser($this->getCurrentUser());
        }
        $query->setOrder($this->getOrder());
        $query->setOrderBy($this->getOrderBy());
        $query->setSearchTerm($this->getSearchTerm());
        $query->setPage($this->getPage());
        $query->setPageSize($this->getPageSize());

        foreach ($this->getTeams() as $team) {
            $query->addTeam($team);
        }

        if ($this instanceof VisibilityInterface && $query instanceof VisibilityInterface) {
            $query->setVisibility($this->getVisibility());
        }

        return $query;
    }
}
