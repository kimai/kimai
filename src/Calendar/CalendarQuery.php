<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Entity\User;
use App\Form\Type\CalendarViewType;
use DateTimeInterface;

class CalendarQuery
{
    private ?DateTimeInterface $date = null;
    private string $view = CalendarViewType::DEFAULT_VIEW;
    private ?User $user = null;

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function setView(string $view): void
    {
        $this->view = match($view){
            'agendaDay', 'day' => 'day',
            'agendaWeek', 'week' => 'week',
            default => 'month',
        };
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
