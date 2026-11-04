<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

use App\Entity\Bookmark;
use App\Entity\User;
use App\Event\DashboardEvent;
use App\Repository\BookmarkRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Manages the widgets a user has placed on their dashboard.
 */
final class DashboardService
{
    public const BOOKMARK_TYPE = 'dashboard';
    public const BOOKMARK_NAME = 'default';

    /**
     * @var array<WidgetInterface>|null
     */
    private ?array $widgets = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WidgetService $service,
        private readonly BookmarkRepository $repository,
        private readonly AuthorizationCheckerInterface $security
    )
    {
    }

    /**
     * All widgets the given user is allowed to see.
     *
     * @return array<WidgetInterface>
     */
    public function getAvailableWidgets(User $user): array
    {
        if ($this->widgets === null) {
            $all = [];
            foreach ($this->service->getAllWidgets() as $widget) {
                $widget->setUser($user);

                $permissions = $widget->getPermissions();
                if (\count($permissions) > 0) {
                    $add = false;
                    foreach ($permissions as $perm) {
                        if ($this->security->isGranted($perm)) {
                            $add = true;
                            break;
                        }
                    }

                    if (!$add) {
                        continue;
                    }
                }
                $all[] = $widget;
            }
            $this->widgets = $all;
        }

        return $this->widgets;
    }

    /**
     * The widget with the given ID, but only if the user may add it to their dashboard.
     *
     * Internal widgets and widgets without a title are not offered in the "add widget" menu,
     * so they may not be added through a crafted request either.
     */
    public function findSelectableWidget(User $user, string $widgetId): ?WidgetInterface
    {
        foreach ($this->getAvailableWidgets($user) as $widget) {
            if ($widget->getId() !== $widgetId) {
                continue;
            }

            if ($widget->isInternal() || empty($widget->getTitle())) {
                return null;
            }

            return $widget;
        }

        return null;
    }

    public function getBookmark(User $user): ?Bookmark
    {
        return $this->repository->findBookmark($user, self::BOOKMARK_TYPE, self::BOOKMARK_NAME);
    }

    /**
     * @return array<string>
     */
    private function getDefaultConfig(User $user): array
    {
        $event = new DashboardEvent($user);

        // default widgets
        $dashboard = [
            'PaginatedWorkingTimeChart',
            //'UserAmountToday',
            //'UserAmountWeek',
            //'UserAmountMonth',
            //'UserAmountYear',
            //'UserTeams',
            //'UserTeamProjects',
            'DurationToday',
            'DurationWeek',
            'DurationMonth',
            'DurationYear',
            //'ActiveUsersToday',
            //'ActiveUsersWeek',
            //'ActiveUsersMonth',
            //'ActiveUsersYear',
            //'AmountToday',
            //'AmountWeek',
            //'AmountMonth',
            //'AmountYear',
            //'TotalsUser',
            //'TotalsCustomer',
            //'TotalsProject',
            //'TotalsActivity',
        ];

        foreach ($dashboard as $widgetName) {
            $event->addWidget($widgetName);
        }

        $this->eventDispatcher->dispatch($event);

        return $event->getWidgets();
    }

    /**
     * The list of widget names and options for a user, falling back to the default dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserWidgets(User $user): array
    {
        $bookmark = $this->getBookmark($user);
        if ($bookmark !== null) {
            return $bookmark->getContent();
        }

        $widgets = [];

        foreach ($this->getDefaultConfig($user) as $name) {
            $widgets[] = ['id' => $name, 'options' => []];
        }

        return $widgets;
    }

    /**
     * @param array<int, array<string, mixed>> $widgets
     */
    public function saveUserWidgets(User $user, array $widgets): void
    {
        $bookmark = $this->getBookmark($user);
        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setType(self::BOOKMARK_TYPE);
            $bookmark->setName(self::BOOKMARK_NAME);
        }
        $bookmark->setContent($widgets);

        $this->repository->saveBookmark($bookmark);
    }

    /**
     * Adds the widget to the users dashboard, returns false if it was already there.
     */
    public function addUserWidget(User $user, WidgetInterface $widget): bool
    {
        $widgets = $this->getUserWidgets($user);

        foreach ($widgets as $setting) {
            if ($setting['id'] === $widget->getId()) {
                return false;
            }
        }

        $widgets[] = ['id' => $widget->getId(), 'options' => []];

        $this->saveUserWidgets($user, $widgets);

        return true;
    }

    /**
     * Drops the user configuration, so the default dashboard is shown again.
     */
    public function resetUserWidgets(User $user): void
    {
        $bookmark = $this->getBookmark($user);
        if ($bookmark !== null) {
            $this->repository->deleteBookmark($bookmark);
        }
    }
}
