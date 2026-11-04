<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AbstractThemeEvent extends Event implements \Stringable
{
    private string $content = '';

    public function addContent(string $content): void
    {
        $this->content .= $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
