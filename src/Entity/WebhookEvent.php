<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

class WebhookEvent
{
    private string $name;
    private WebhookConfiguration $configuration;

    public function __construct(string $name, WebhookConfiguration $configuration)
    {
        $this->name = $name;
        $this->configuration = $configuration;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfiguration(): WebhookConfiguration
    {
        return $this->configuration;
    }
}
