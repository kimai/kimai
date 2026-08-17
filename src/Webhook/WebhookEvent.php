<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Webhook;

readonly class WebhookEvent
{
    public function __construct(
        private string $name,
        private string $url,
        #[\SensitiveParameter]
        private string $secret,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
