<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use App\Configuration\OidcConfigurationInterface;
use Jumbojett\OpenIDConnectClient;

/**
 * @final
 */
class OidcClientFactory
{
    public function __construct(
        private readonly OidcConfigurationInterface $configuration
    ) {
    }

    public function create(): OpenIDConnectClient
    {
        $client = new OpenIDConnectClient(
            $this->configuration->getProviderUrl(),
            $this->configuration->getClientId(),
            $this->configuration->getClientSecret()
        );
        $client->addScope(['email', 'profile']);

        if ($this->configuration->getRolesMapping()) {
            $client->addScope(['groups']);
        }

        return $client;
    }
}
