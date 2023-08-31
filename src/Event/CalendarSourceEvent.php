<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Calendar\CalendarSource;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class CalendarSourceEvent extends Event
{
    /**
     * @var CalendarSource[]
     */
    private array $sources = [];

    public function __construct(private User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addSource(CalendarSource $source): void
    {
        $this->sources[] = $source;
    }

    /**
     * @return CalendarSource[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }
}
