<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;

class PageActionsEvent extends ThemeEvent
{
    private $action;

    public function __construct(User $user, array $payload, string $action)
    {
        if (!\array_key_exists('actions', $payload)) {
            $payload['actions'] = [];
        }
        parent::__construct($user, $payload);
        $this->action = $action;
    }

    public function getActionName(): string
    {
        return $this->action;
    }

    public function getActions(): array
    {
        return $this->payload['actions'];
    }

    public function setActions(array $actions): void
    {
        $this->payload['actions'] = $actions;
    }
}
