<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

class WebhookConfiguration
{
    private string $name;
    private string $url;
    private string $transport;
    private string $secret;
    private string $authentication;

    public function __construct(
        string $name,
        string $url,
        string $transport,
        #[\SensitiveParameter]
        string $secret,
        string $authentication
    )
    {
        $this->name = $name;
        $this->url = $url;
        $this->transport = $transport;
        $this->secret = $secret;
        $this->authentication = $authentication;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getAuthentication(): string
    {
        return $this->authentication;
    }
}
