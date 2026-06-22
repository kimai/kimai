<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use App\Configuration\OidcConfigurationInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OidcDiscovery
{
    private const CACHE_TTL = 3600;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $metadata = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly OidcConfigurationInterface $configuration,
    ) {
    }

    public function getMetadata(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        $providerUrl = rtrim($this->configuration->getProviderUrl(), '/');
        $cacheKey = 'oidc.discovery.' . sha1($providerUrl);

        $this->metadata = $this->cache->get($cacheKey, function (ItemInterface $item) use ($providerUrl): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->httpClient
                ->request('GET', $providerUrl . '/.well-known/openid-configuration')
                ->toArray();
        });

        return $this->metadata;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->getRequiredString('authorization_endpoint');
    }

    public function getTokenEndpoint(): string
    {
        return $this->getRequiredString('token_endpoint');
    }

    public function getUserInfoEndpoint(): string
    {
        return $this->getRequiredString('userinfo_endpoint');
    }

    public function getIssuer(): string
    {
        return $this->getRequiredString('issuer');
    }

    private function getRequiredString(string $key): string
    {
        $metadata = $this->getMetadata();
        if (!isset($metadata[$key]) || !\is_string($metadata[$key]) || $metadata[$key] === '') {
            throw new \RuntimeException(\sprintf('OIDC discovery document is missing "%s".', $key));
        }

        return $metadata[$key];
    }
}
