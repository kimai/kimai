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

final class ThemeEvent extends Event
{
    public const JAVASCRIPT = 'app.theme.javascript';
    public const STYLESHEET = 'app.theme.css';
    public const HTML_HEAD = 'app.theme.html_head';
    public const CONTENT_BEFORE = 'app.theme.content_before';
    public const CONTENT_START = 'app.theme.content_start';
    public const CONTENT_END = 'app.theme.content_end';
    public const CONTENT_AFTER = 'app.theme.content_after';

    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var string
     */
    protected $content = '';
    /**
     * @var mixed
     */
    protected $payload = null;

    public function __construct(?User $user = null, $payload = null)
    {
        $this->user = $user;
        $this->payload = $payload;
    }

    /**
     * @deprecated since 1.0
     */
    public function getUser(): ?User
    {
        @trigger_error('Using ThemeEvent::getUser() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

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

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     * @return ThemeEvent
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }
}
