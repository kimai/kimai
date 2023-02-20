<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Repository\Query\BaseQuery;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Traversable;

final class DataTable implements \Countable, \IteratorAggregate
{
    private ?Pagination $pagination = null;
    private ?FormInterface $searchForm = null;
    private ?FormInterface $batchForm = null;
    private array $columns = [];
    private array $reloadEvents = [];
    private bool $configuration = true;
    private bool $sticky = true;
    private ?string $paginationRoute = null;

    public function __construct(private string $tableName, private BaseQuery $query)
    {
    }

    public function hasResults(): bool
    {
        return $this->pagination !== null && $this->pagination->count() > 0;
    }

    public function getResults(): ?iterable
    {
        return $this->pagination;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }

    public function setPagination(?Pagination $pagination): void
    {
        $this->pagination = $pagination;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getQuery(): BaseQuery
    {
        return $this->query;
    }

    public function getSearchForm(): ?FormView
    {
        return $this->searchForm?->createView();
    }

    public function setSearchForm(?FormInterface $searchForm): void
    {
        $this->searchForm = $searchForm;
    }

    public function hasBatchForm(): bool
    {
        return $this->batchForm !== null;
    }

    public function getBatchForm(): ?FormView
    {
        return $this->batchForm?->createView();
    }

    public function setBatchForm(?FormInterface $batchForm): void
    {
        $this->batchForm = $batchForm;

        if (!\array_key_exists('id', $this->columns)) {
            $this->addColumn('id', [
                'class' => 'alwaysVisible multiCheckbox',
                'orderBy' => false,
                'title' => false,
                'batchUpdate' => true
            ]);
        }
    }

    public function getSortedColumnNames(): array
    {
        $columns = [];
        foreach ($this->columns as $key => $options) {
            $columns[$key] = \array_key_exists('data', $options) ? $options['data'] : [];
        }

        return $columns;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * Supported $column options:
     * - class
     * - title
     * - translation_domain
     * - orderBy (string|false)
     * - order (desc, asc)
     *
     * @param string $name
     * @param array $column
     * @return void
     */
    public function addColumn(string $name, array $column = []): void
    {
        if (!\array_key_exists('class', $column)) {
            $column['class'] = '';
        }
        $this->columns[$name] = $column;
    }

    public function deactivateConfiguration(): void
    {
        $this->configuration = false;
    }

    public function hasConfiguration(): bool
    {
        return $this->configuration;
    }

    public function getPaginationRoute(): ?string
    {
        return $this->paginationRoute;
    }

    public function setPaginationRoute(?string $paginationRoute): void
    {
        $this->paginationRoute = $paginationRoute;
    }

    public function getOptions(): array
    {
        $options = [
            'columnConfig' => false,
            'sticky' => $this->sticky,
        ];

        if (\count($this->reloadEvents) > 0) {
            $options['reload'] = $this->getReloadEvents();
        }

        return $options;
    }

    public function getReloadEvents(): string
    {
        return implode(' ', $this->reloadEvents);
    }

    public function setReloadEvents(string|array $reloadEvents): void
    {
        if (\is_string($reloadEvents)) {
            $reloadEvents = explode(' ', $reloadEvents);
        }
        $this->reloadEvents = $reloadEvents;
    }

    public function addReloadEvent(string $reloadEvent): void
    {
        $this->reloadEvents[] = $reloadEvent;
    }

    public function setSticky(bool $sticky = true): void
    {
        $this->sticky = $sticky;
    }

    public function getIterator(): Traversable
    {
        return $this->pagination?->getIterator();
    }

    public function count(): int
    {
        if ($this->pagination === null) {
            return 0;
        }

        return $this->pagination->count();
    }
}
