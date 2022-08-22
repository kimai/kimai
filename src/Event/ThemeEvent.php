<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ThemeEvent extends Event
{
    public const JAVASCRIPT = 'app.theme.javascript';
    public const STYLESHEET = 'app.theme.css';
    public const HTML_HEAD = 'app.theme.html_head';
    public const CONTENT_BEFORE = 'app.theme.content_before';
    public const CONTENT_START = 'app.theme.content_start';
    public const CONTENT_END = 'app.theme.content_end';
    public const CONTENT_AFTER = 'app.theme.content_after';

    private string $content = '';
    protected mixed $payload;

    public function __construct(private ?User $user = null, mixed $payload = null)
    {
        $this->payload = $payload;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function addContent(string $content): ThemeEvent
    {
        $this->content .= $content;

        return $this;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function setPayload(mixed $payload): void
    {
        $this->payload = $payload;
    }
}
