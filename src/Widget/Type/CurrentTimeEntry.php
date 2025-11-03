<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

/**
 * Widget to display the current user's active time entry with full details
 */
final class CurrentTimeEntry extends AbstractWidgetType
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
        return array_merge([
            'color' => WidgetInterface::COLOR_TODAY,
            'icon' => 'duration',
        ], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_own_timesheet'];
    }

    public function getId(): string
    {
        return 'currentTimeEntry';
    }

    public function getTitle(): string
    {
        return 'Current Time Entry';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        try {
            $user = $options['user'] ?? null;
            if ($user === null) {
                return null;
            }

            // Get the active timesheet for the current user
            $activeEntries = $this->repository->getActiveEntries($user);
            
            if (empty($activeEntries)) {
                return null;
            }

            // Return the first active entry (users typically have only one)
            return $activeEntries[0];
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-current-time-entry.html.twig';
    }
}
