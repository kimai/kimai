<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

class RecentActivitiesSource implements DragAndDropSource
{
    /**
     * @var DragAndDropEntry[]
     */
    private $entries;

    /**
     * @param DragAndDropEntry[] $entries
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function getTitle(): string
    {
        return 'recent.activities';
    }

    public function getRoute(): string
    {
        return 'post_timesheet';
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    /**
     * @return array<string, string>
     */
    public function getRouteParams(): array
    {
        return ['full' => 'true'];
    }

    /**
     * @return array<string, string>
     */
    public function getRouteReplacer(): array
    {
        return [];
    }

    /**
     * @return DragAndDropEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getBlockInclude(): ?string
    {
        return 'calendar/drag-drop.html.twig';
    }
}
