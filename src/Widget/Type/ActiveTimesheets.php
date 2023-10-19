<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class ActiveTimesheets extends AbstractWidgetType
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        // we can safely assume that the user can see
        $route = 'admin_timesheet';

        return array_merge([
            'color' => WidgetInterface::COLOR_TOTAL,
            'icon' => 'duration',
            'route' => $route,
            'routeOptions' => ['state' => TimesheetQuery::STATE_RUNNING],
        ], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        // if you ever loosen that check, make sure that the above link is probably removed
        return ['view_all_data'];
    }

    public function getId(): string
    {
        return 'activeRecordings';
    }

    public function getTitle(): string
    {
        return 'stats.activeRecordings';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        try {
            return $this->repository->countActiveEntries();
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
