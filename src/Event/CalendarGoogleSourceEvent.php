<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Calendar\GoogleSource;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class CalendarGoogleSourceEvent extends Event
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var GoogleSource[]
     */
    private $sources = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addSource(GoogleSource $source): CalendarGoogleSourceEvent
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @return GoogleSource[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }
}
