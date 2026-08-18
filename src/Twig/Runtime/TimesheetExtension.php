<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TicktackExcludeEvent;
use App\Model\FavoriteTimesheet;
use App\Repository\TimesheetRepository;
use App\Timesheet\FavoriteRecordService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class TimesheetExtension implements RuntimeExtensionInterface
{
    /** @var array<array<string, int>>|null */
    private ?array $cachedExcludes = null;

    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly FavoriteRecordService $favoriteRecordService,
        private readonly SystemConfiguration $configuration,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    /**
     * @return array<Timesheet>
     */
    public function activeEntries(User $user, bool $ticktack = false): array
    {
        return $this->repository->getActiveEntries($user, $ticktack);
    }

    /**
     * Returns active entries filtered by plugin-defined exclude rules.
     *
     * @return array<Timesheet>
     */
    public function ticktackEntries(User $user): array
    {
        $entries = $this->repository->getActiveEntries($user);
        $excludes = $this->ticktackExcludes();

        if (empty($excludes)) {
            return $entries;
        }

        return array_values(array_filter($entries, function (Timesheet $entry) use ($excludes) {
            foreach ($excludes as $rule) {
                $match = true;
                foreach ($rule as $key => $id) {
                    $getter = 'get' . ucfirst($key);
                    if (!method_exists($entry, $getter) || $entry->$getter() === null || $entry->$getter()->getId() !== $id) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array<array<string, int>>
     */
    public function ticktackExcludes(): array
    {
        if ($this->cachedExcludes === null) {
            $event = new TicktackExcludeEvent();
            $this->eventDispatcher->dispatch($event);
            $this->cachedExcludes = $event->getExcludes();
        }

        return $this->cachedExcludes;
    }

    public function activeEntriesHardLimit(): int
    {
        return $this->configuration->getTimesheetActiveEntriesHardLimit();
    }

    /**
     * @return array<FavoriteTimesheet>
     */
    public function favoriteEntries(User $user, int $limit = 5): array
    {
        return $this->favoriteRecordService->favoriteEntries($user, $limit);
    }
}
