<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\User;
use App\Repository\Query\TimesheetQuery;

final class Export
{
    /**
     * @var User
     */
    private $createdBy;
    /**
     * @var ExportItemInterface[]
     */
    private $items;
    /**
     * @var string
     */
    private $language;
    /**
     * @var TimesheetQuery
     */
    private $query;

    /**
     * @param ExportItemInterface[] $items
     * @param TimesheetQuery $query
     * @param User $createdBy
     * @param string $language
     */
    public function __construct(array $items, TimesheetQuery $query, User $createdBy, string $language)
    {
        $this->items = $items;
        $this->query = $query;
        $this->createdBy = $createdBy;
        $this->language = $language;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getQuery(): TimesheetQuery
    {
        return $this->query;
    }
}
