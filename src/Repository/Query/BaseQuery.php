<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

use App\Entity\Bookmark;
use App\Entity\Team;
use App\Entity\User;
use App\Form\Model\DateRange;
use App\Utils\EquatableInterface;
use App\Utils\SearchTerm;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;

/**
 * Base class for advanced Repository queries.
 */
class BaseQuery
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';
    public const DEFAULT_PAGESIZE = 50;

    /** @var array<string, string|int|null|bool|array<mixed>|DateRange> */
    private array $defaults = [
        'page' => 1,
        'pageSize' => self::DEFAULT_PAGESIZE,
        'orderBy' => 'id',
        'order' => self::ORDER_ASC,
        'searchTerm' => null,
    ];
    private int $page = 1;
    private int $pageSize = self::DEFAULT_PAGESIZE;
    private string $orderBy = 'id';
    private string $order = self::ORDER_ASC;
    /**
     * @var array<string, string>
     */
    private array $orderGroups = [];
    private ?User $currentUser = null;
    /**
     * @var array<Team>
     */
    private array $teams = [];
    private ?SearchTerm $searchTerm = null;
    private ?Bookmark $bookmark = null;
    private ?string $name = null;
    private bool $bookmarkSearch = false;

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
     * By setting the current user, you activate (team) permission checks.
     *
     * @param User $user
     * @return self
     */
    public function setCurrentUser(?User $user): self
    {
        $this->currentUser = $user;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(?int $page): self
    {
        if ($page !== null && $page > 0) {
            $this->page = $page;
        }

        return $this;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(?int $pageSize): self
    {
        if ($pageSize !== null && $pageSize > 0) {
            $this->pageSize = $pageSize;
        }

        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): self
    {
        if (\in_array($order, [self::ORDER_ASC, self::ORDER_DESC])) {
            $this->order = $order;
        }

        return $this;
    }

    public function addOrderGroup(string $orderBy, string $order): void
    {
        $this->orderGroups[$orderBy] = $order;
    }

    public function getOrderGroups(): array
    {
        if (empty($this->orderGroups)) {
            return [$this->orderBy => $this->order];
        }

        return $this->orderGroups;
    }

    public function hasSearchTerm(): bool
    {
        return null !== $this->searchTerm;
    }

    public function getSearchTerm(): ?SearchTerm
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(?SearchTerm $searchTerm): self
    {
        $this->searchTerm = $searchTerm;

        return $this;
    }

    protected function set(string $name, mixed $value): void
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            \call_user_func([$this, $method], $value);

            return;
        }

        if (str_ends_with($name, 's')) {
            $method = 'add' . ucfirst(substr($name, 0, \strlen($name) - 1));
            if (method_exists($this, $method) && \is_array($value)) {
                foreach ($value as $v) {
                    \call_user_func([$this, $method], $v);
                }

                return;
            }
        }

        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        }
    }

    protected function get(string $name): mixed
    {
        $methods = ['get' . ucfirst($name), 'is' . ucfirst($name), 'has' . ucfirst($name)];
        foreach ($methods as $method) {
            if (method_exists($this, $method)) {
                return \call_user_func([$this, $method]);
            }
        }

        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * You have to add ALL user facing form fields as default!
     *
     * @param array<string, string|int|null|bool|array<mixed>|DateRange> $defaults
     * @return self
     */
    protected function setDefaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        foreach ($this->defaults as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * @param FormErrorIterator<FormError> $errors
     * @return $this
     */
    public function resetByFormError(FormErrorIterator $errors): self
    {
        foreach ($errors as $error) {
            $key = $error->getOrigin()?->getName();
            if ($key !== null && \array_key_exists($key, $this->defaults)) {
                $this->set($key, $this->defaults[$key]);
            }
        }

        return $this;
    }

    public function setBookmark(Bookmark $bookmark): void
    {
        $this->bookmark = $bookmark;
    }

    public function getBookmark(): ?Bookmark
    {
        return $this->bookmark;
    }

    public function hasBookmark(): bool
    {
        return null !== $this->bookmark;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        if (null !== $this->name) {
            return $this->name;
        }

        $shortClass = explode('\\', static::class);

        return array_pop($shortClass);
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

    public function isDefaultFilter(string $filter): bool
    {
        if (!\array_key_exists($filter, $this->defaults)) {
            return false;
        }

        $expectedValue = $this->defaults[$filter];

        return $this->matchesFilter($filter, $expectedValue);
    }

    public function matchesFilter(string $filter, $expectedValue): bool
    {
        $currentValue = $this->get($filter);

        if (\is_object($currentValue)) {
            if ($currentValue instanceof EquatableInterface) {
                return $currentValue->isEqualTo($expectedValue);
            }

            // this is a loose comparison by choice, as == compares object type and content
            // instead of === which only compares if both sides are the same instance
            if ($currentValue != $expectedValue) { // @phpstan-ignore-line
                return false;
            }
        } else {
            if ($currentValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    public function countFilter(): int
    {
        $filter = 0;

        foreach (array_keys($this->defaults) as $key) {
            if ($key === 'page') {
                continue;
            }

            if (!$this->isDefaultFilter($key)) {
                $filter++;
            }
        }

        return $filter;
    }

    public function resetFilter(): void
    {
        foreach ($this->defaults as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function flagAsBookmarkSearch(): void
    {
        $this->bookmarkSearch = true;
    }

    public function isBookmarkSearch(): bool
    {
        return $this->bookmarkSearch;
    }
}
