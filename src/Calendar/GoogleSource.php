<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

final class GoogleSource
{
    public function __construct(private string $id, private string $uri, private ?string $color = null)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}
