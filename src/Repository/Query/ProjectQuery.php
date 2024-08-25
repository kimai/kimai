<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Query;

class ProjectQuery extends BaseQuery implements VisibilityInterface
{
    use VisibilityTrait;
    use CustomerTrait;

    public const PROJECT_ORDER_ALLOWED = [
        'name',
        'description' => 'comment',
        'project_number' => 'number',
        'customer',
        'orderNumber',
        'orderDate',
        'project_start',
        'project_end',
        'budget',
        'timeBudget',
        'visible'
    ];

    private ?\DateTime $projectStart = null;
    private ?\DateTime $projectEnd = null;
    private ?bool $globalActivities = null;
    /**
     * @var array<int>
     */
    private array $projectIds = [];
    /**
     * @var array<ProjectQueryHydrate>
     */
    private array $hydrate = [];

    public function __construct()
    {
        $this->setDefaults([
            'orderBy' => 'name',
            'customers' => [],
            'projectStart' => null,
            'projectEnd' => null,
            'visibility' => VisibilityInterface::SHOW_VISIBLE,
            'globalActivities' => null,
            'projectIds' => [],
        ]);
    }

    protected function copyFrom(BaseQuery $query): void
    {
        parent::copyFrom($query);

        if (method_exists($query, 'getCustomers')) {
            $this->setCustomers($query->getCustomers());
        }

        if ($query instanceof ProjectQuery) {
            $this->setProjectIds($query->getProjectIds());
            $this->setProjectStart($query->getProjectStart());
            $this->setProjectEnd($query->getProjectEnd());
            $this->setGlobalActivities($query->getGlobalActivities());
            foreach ($query->getHydrate() as $hydrate) {
                $this->addHydrate($hydrate);
            }
        }
    }

    public function getProjectStart(): ?\DateTime
    {
        return $this->projectStart;
    }

    public function setProjectStart(?\DateTime $projectStart): ProjectQuery
    {
        $this->projectStart = $projectStart;

        return $this;
    }

    public function getProjectEnd(): ?\DateTime
    {
        return $this->projectEnd;
    }

    public function setProjectEnd(?\DateTime $projectEnd): ProjectQuery
    {
        $this->projectEnd = $projectEnd;

        return $this;
    }

    public function getGlobalActivities(): ?bool
    {
        return $this->globalActivities;
    }

    public function setGlobalActivities(?bool $globalActivities): void
    {
        $this->globalActivities = $globalActivities;
    }

    /**
     * @param array<int> $ids
     */
    public function setProjectIds(array $ids): void
    {
        $this->projectIds = $ids;
    }

    /**
     * @return int[]
     */
    public function getProjectIds(): array
    {
        return $this->projectIds;
    }

    private function addHydrate(ProjectQueryHydrate $hydrate): void
    {
        if (!\in_array($hydrate, $this->hydrate, true)) {
            $this->hydrate[] = $hydrate;
        }
    }

    /**
     * @return ProjectQueryHydrate[]
     */
    public function getHydrate(): array
    {
        return $this->hydrate;
    }

    public function loadTeams(): void
    {
        $this->addHydrate(ProjectQueryHydrate::TEAMS);
    }
}
