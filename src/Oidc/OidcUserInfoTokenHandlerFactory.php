<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\Security\Http\AccessToken\Oidc\OidcUserInfoTokenHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OidcUserInfoTokenHandlerFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OidcDiscovery $discovery,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function create(): OidcUserInfoTokenHandler
    {
        $scopedClient = ScopingHttpClient::forBaseUri(
            $this->httpClient,
            $this->discovery->getUserInfoEndpoint()
        );

        return new OidcUserInfoTokenHandler($scopedClient, $this->logger, 'email');
    }
}
