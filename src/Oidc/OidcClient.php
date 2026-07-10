<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use App\Configuration\OidcConfigurationInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles the low-level OpenID Connect "Authorization Code Flow":
 * endpoint discovery, building the authorization URL, exchanging the
 * authorization code for tokens and fetching the user claims.
 *
 * @final
 */
class OidcClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OidcConfigurationInterface $configuration,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Builds the URL the user is redirected to, in order to authenticate at the identity provider.
     */
    public function getAuthorizationUrl(string $redirectUri, string $state, string $nonce, ?string $codeVerifier = null): string
    {
        $endpoint = $this->configuration->getAuthorizationUrl();
        if ($endpoint === null || $endpoint === '') {
            $endpoint = $this->getFromDiscovery('authorization_endpoint');
        }

        $scopes = $this->configuration->getScopes();
        if (!\in_array('openid', $scopes, true)) {
            array_unshift($scopes, 'openid');
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->configuration->getClientId(),
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'nonce' => $nonce,
        ];

        // PKCE (RFC 7636): providers without PKCE support will ignore these parameters
        if ($codeVerifier !== null && $codeVerifier !== '') {
            $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }

        $separator = str_contains($endpoint, '?') ? '&' : '?';

        return $endpoint . $separator . http_build_query($params);
    }

    /**
     * Exchanges the authorization code for tokens and returns the resolved user claims.
     *
     * @return array<string, mixed>
     */
    public function fetchUserClaims(string $code, string $redirectUri, string $expectedNonce, ?string $codeVerifier = null): array
    {
        $tokens = $this->exchangeCode($code, $redirectUri, $codeVerifier);

        if (!isset($tokens['access_token']) || !\is_string($tokens['access_token'])) {
            throw new AuthenticationException('OIDC token response did not contain an access_token.');
        }

        // validate the ID token (issuer, audience, expiration and nonce) when present
        if (isset($tokens['id_token']) && \is_string($tokens['id_token'])) {
            $this->validateIdToken($tokens['id_token'], $expectedNonce);
        }

        return $this->fetchUserInfo($tokens['access_token']);
    }

    /**
     * @return array<string, mixed>
     */
    private function exchangeCode(string $code, string $redirectUri, ?string $codeVerifier = null): array
    {
        $endpoint = $this->configuration->getTokenUrl();
        if ($endpoint === null || $endpoint === '') {
            $endpoint = $this->getFromDiscovery('token_endpoint');
        }

        $body = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $this->configuration->getClientId(),
            'client_secret' => $this->configuration->getClientSecret(),
        ];

        if ($codeVerifier !== null && $codeVerifier !== '') {
            $body['code_verifier'] = $codeVerifier;
        }

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (HttpExceptionInterface $ex) {
            $this->logger->critical('OIDC token exchange failed: ' . $ex->getMessage());
            throw new AuthenticationException('OIDC token exchange failed.');
        }

        if (isset($data['error'])) {
            $error = \is_string($data['error']) ? $data['error'] : 'unknown';
            $description = \is_string($data['error_description'] ?? null) ? $data['error_description'] : $error;
            $this->logger->critical('OIDC token exchange returned an error: ' . $description);
            throw new AuthenticationException('OIDC token exchange returned an error.');
        }

        if ($statusCode !== 200) {
            $this->logger->critical('OIDC token endpoint returned unexpected status code: ' . $statusCode);
            throw new AuthenticationException('OIDC token exchange failed.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserInfo(string $accessToken): array
    {
        $endpoint = $this->configuration->getUserInfoUrl();
        if ($endpoint === null || $endpoint === '') {
            $endpoint = $this->getFromDiscovery('userinfo_endpoint');
        }

        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (HttpExceptionInterface $ex) {
            $this->logger->critical('OIDC userinfo request failed: ' . $ex->getMessage());
            throw new AuthenticationException('OIDC userinfo request failed.');
        }

        if ($statusCode !== 200) {
            $this->logger->critical('OIDC userinfo endpoint returned unexpected status code: ' . $statusCode);
            throw new AuthenticationException('OIDC userinfo request failed.');
        }

        if (\count($data) === 0) {
            throw new AuthenticationException('OIDC userinfo response was empty.');
        }

        return $data;
    }

    /**
     * Validates the ID token claims. The token itself is retrieved through the
     * trusted back-channel (direct TLS connection to the token endpoint), which is
     * why we validate the standard claims (iss, aud, exp, nonce) instead of the
     * signature.
     */
    private function validateIdToken(string $idToken, string $expectedNonce): void
    {
        $parts = explode('.', $idToken);
        if (\count($parts) !== 3) {
            throw new AuthenticationException('OIDC ID token is malformed.');
        }

        $payload = $this->decodeSegment($parts[1]);

        // nonce binds the token to this specific login attempt (replay protection)
        if (($payload['nonce'] ?? null) !== $expectedNonce) {
            throw new AuthenticationException('OIDC ID token nonce mismatch.');
        }

        $issuer = $this->configuration->getIssuer();
        if ($issuer !== null && $issuer !== '' && isset($payload['iss']) && rtrim((string) $payload['iss'], '/') !== rtrim($issuer, '/')) {
            throw new AuthenticationException('OIDC ID token issuer mismatch.');
        }

        if (isset($payload['aud'])) {
            $audiences = \is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];
            if (!\in_array($this->configuration->getClientId(), $audiences, true)) {
                throw new AuthenticationException('OIDC ID token audience mismatch.');
            }
        }

        // "exp" is a REQUIRED claim in ID tokens, so a missing value is treated as an error
        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) {
            throw new AuthenticationException('OIDC ID token is missing the "exp" claim.');
        }

        if ((int) $payload['exp'] < time()) {
            throw new AuthenticationException('OIDC ID token has expired.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSegment(string $segment): array
    {
        $decoded = base64_decode(strtr($segment, '-_', '+/'), true);
        if ($decoded === false) {
            throw new AuthenticationException('OIDC ID token could not be decoded.');
        }

        $data = json_decode($decoded, true);
        if (!\is_array($data)) {
            throw new AuthenticationException('OIDC ID token payload is invalid.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function getFromDiscovery(string $key): string
    {
        $config = $this->getDiscoveryDocument();

        if (!isset($config[$key]) || !\is_string($config[$key]) || $config[$key] === '') {
            throw new AuthenticationException(\sprintf('OIDC discovery document is missing the "%s" endpoint.', $key));
        }

        return $config[$key];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDiscoveryDocument(): array
    {
        $issuer = $this->configuration->getIssuer();
        if ($issuer === null || $issuer === '') {
            throw new AuthenticationException('OIDC issuer is not configured, cannot discover endpoints.');
        }

        $url = rtrim($issuer, '/') . '/.well-known/openid-configuration';
        $cacheKey = 'oidc_discovery_' . sha1($url);

        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            /** @var array<string, mixed> $cached */
            $cached = $item->get();

            return $cached;
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['Accept' => 'application/json'],
            ]);

            $statusCode = $response->getStatusCode();
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (HttpExceptionInterface $ex) {
            $this->logger->critical('OIDC discovery request failed: ' . $ex->getMessage());
            throw new AuthenticationException('OIDC discovery request failed.');
        }

        if ($statusCode !== 200) {
            $this->logger->critical('OIDC discovery endpoint returned unexpected status code: ' . $statusCode);
            throw new AuthenticationException('OIDC discovery request failed.');
        }

        // the spec requires that the "issuer" in the discovery document matches the configured issuer
        if (!isset($data['issuer']) || !\is_string($data['issuer']) || rtrim($data['issuer'], '/') !== rtrim($issuer, '/')) {
            $this->logger->critical('OIDC discovery document issuer does not match the configured issuer.');
            throw new AuthenticationException('OIDC discovery document issuer mismatch.');
        }

        $item->set($data);
        $item->expiresAfter(3600);
        $this->cache->save($item);

        return $data;
    }
}
