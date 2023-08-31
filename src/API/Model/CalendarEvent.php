<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\Utils\Color;
use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('all')]
final class CalendarEvent
{
    /**
     * Calendar entry title
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $title; // @phpstan-ignore-line
    /**
     * Calendar background color
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $color = null; // @phpstan-ignore-line
    /**
     * Calendar text color
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $textColor = null;
    /**
     * If this entry is all-day long
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    private bool $allDay = false; // @phpstan-ignore-line
    /**
     * Calendar entry start date
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'DateTime')]
    private \DateTimeInterface $start; // @phpstan-ignore-line
    /**
     * Calendar entry end date
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'DateTime')]
    private \DateTimeInterface $end; // @phpstan-ignore-line

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setStart(\DateTimeInterface $start): void
    {
        $this->start = $start;
    }

    public function setEnd(\DateTimeInterface $end): void
    {
        $this->end = $end;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
        if ($color !== null && $this->textColor === null) {
            $this->textColor = (new Color())->getFontContrastColor($color);
        }
    }

    public function setAllDay(bool $allDay): void
    {
        $this->allDay = $allDay;
    }

    public function setTextColor(?string $textColor): void
    {
        $this->textColor = $textColor;
    }
}
