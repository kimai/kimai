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
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string|null
     */
    private $color;

    public function __construct(string $id, string $uri, ?string $color = null)
    {
        $this->id = $id;
        $this->uri = $uri;
        $this->color = $color;
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
