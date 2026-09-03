<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Configuration\OidcConfiguration;
use App\Oidc\OidcClient;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(OidcClient::class)]
class OidcClientTest extends TestCase
{
    private function getConfiguration(array $override = []): OidcConfiguration
    {
        $settings = array_merge([
            'activate' => true,
            'client_id' => 'my-client-id',
            'client_secret' => 'my-client-secret',
            'issuer' => 'https://id.example.com',
            'authorization_url' => 'https://id.example.com/authorize',
            'token_url' => 'https://id.example.com/token',
            'userinfo_url' => 'https://id.example.com/userinfo',
            'scopes' => 'openid profile email',
        ], $override);

        $configuration = SystemConfigurationFactory::create(new TestConfigLoader([]), ['oidc' => $settings]);

        return new OidcConfiguration($configuration);
    }

    private function getSut(HttpClientInterface $httpClient, array $configOverride = []): OidcClient
    {
        return new OidcClient(
            $httpClient,
            $this->getConfiguration($configOverride),
            new ArrayAdapter(),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function createIdToken(array $payload): string
    {
        $encode = static fn (array $data): string => rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');

        return $encode(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $encode($payload) . '.signature';
    }

    public function testGetAuthorizationUrl(): void
    {
        $sut = $this->getSut(new MockHttpClient());

        $url = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state-123', 'nonce-123');

        self::assertStringStartsWith('https://id.example.com/authorize?', $url);

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertEquals('code', $query['response_type']);
        self::assertEquals('my-client-id', $query['client_id']);
        self::assertEquals('https://kimai.local/oidc/callback', $query['redirect_uri']);
        self::assertEquals('openid profile email', $query['scope']);
        self::assertEquals('state-123', $query['state']);
        self::assertEquals('nonce-123', $query['nonce']);
    }

    public function testGetAuthorizationUrlEnforcesOpenidScope(): void
    {
        $sut = $this->getSut(new MockHttpClient(), ['scopes' => 'profile email']);

        $url = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state', 'nonce');

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertEquals('openid profile email', $query['scope']);
    }

    public function testGetAuthorizationUrlUsesDiscovery(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'issuer' => 'https://id.example.com',
                'authorization_endpoint' => 'https://id.example.com/discovered/authorize',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient, ['authorization_url' => '']);

        $url = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state', 'nonce');

        self::assertStringStartsWith('https://id.example.com/discovered/authorize?', $url);
    }

    public function testGetAuthorizationUrlThrowsOnDiscoveryIssuerMismatch(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC discovery document issuer mismatch.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'issuer' => 'https://evil.example.com',
                'authorization_endpoint' => 'https://id.example.com/discovered/authorize',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient, ['authorization_url' => '']);
        $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state', 'nonce');
    }

    public function testGetAuthorizationUrlContainsPkceChallenge(): void
    {
        $sut = $this->getSut(new MockHttpClient());

        $verifier = 'my-code-verifier';
        $url = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state', 'nonce', $verifier);

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        self::assertEquals($expected, $query['code_challenge']);
        self::assertEquals('S256', $query['code_challenge_method']);
    }

    public function testFetchUserClaims(): void
    {
        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://id.example.com',
            'aud' => 'my-client-id',
            'exp' => time() + 3600,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
                'token_type' => 'Bearer',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
            new MockResponse((string) json_encode([
                'sub' => '12345',
                'email' => 'foo@example.com',
                'name' => 'John Doe',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);

        $claims = $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');

        self::assertEquals('12345', $claims['sub']);
        self::assertEquals('foo@example.com', $claims['email']);
        self::assertEquals('John Doe', $claims['name']);
    }

    public function testFetchUserClaimsSendsPkceVerifier(): void
    {
        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://id.example.com',
            'aud' => 'my-client-id',
            'exp' => time() + 3600,
        ]);

        $tokenRequestBody = null;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($idToken, &$tokenRequestBody) {
            if (str_contains($url, '/token')) {
                $tokenRequestBody = $options['body'] ?? null;

                return new MockResponse((string) json_encode([
                    'access_token' => 'the-access-token',
                    'id_token' => $idToken,
                ]), ['response_headers' => ['content-type' => 'application/json']]);
            }

            return new MockResponse((string) json_encode([
                'sub' => '12345',
            ]), ['response_headers' => ['content-type' => 'application/json']]);
        });

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123', 'my-code-verifier');

        self::assertIsString($tokenRequestBody);
        self::assertStringContainsString('code_verifier=my-code-verifier', $tokenRequestBody);
    }

    public function testFetchUserClaimsThrowsOnTokenError(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC token exchange returned an error.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'code expired',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnMissingAccessToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC token response did not contain an access_token.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'token_type' => 'Bearer',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnNonceMismatch(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token nonce mismatch.');

        $idToken = $this->createIdToken([
            'nonce' => 'a-different-nonce',
            'iss' => 'https://id.example.com',
            'aud' => 'my-client-id',
            'exp' => time() + 3600,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnAudienceMismatch(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token audience mismatch.');

        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://id.example.com',
            'aud' => 'a-different-client',
            'exp' => time() + 3600,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnExpiredIdToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token has expired.');

        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://id.example.com',
            'aud' => 'my-client-id',
            'exp' => time() - 60,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnMissingExpClaim(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token is missing the "exp" claim.');

        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://id.example.com',
            'aud' => 'my-client-id',
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnUserInfoErrorStatus(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC userinfo request failed.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
            new MockResponse((string) json_encode(['error' => 'invalid_token']), [
                'http_code' => 401,
                'response_headers' => ['content-type' => 'application/json'],
            ]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnEmptyUserInfo(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC userinfo response was empty.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
            new MockResponse((string) json_encode([]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsWhenTokenResponseIsNotJson(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC token exchange failed.');

        $httpClient = new MockHttpClient([
            new MockResponse('not-a-json-response', ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnTokenErrorStatus(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC token exchange failed.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode(['message' => 'server error']), [
                'http_code' => 500,
                'response_headers' => ['content-type' => 'application/json'],
            ]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsWhenUserInfoResponseIsNotJson(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC userinfo request failed.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
            new MockResponse('not-a-json-response', ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnMalformedIdToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token is malformed.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => 'only.two-parts',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnUndecodableIdToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token could not be decoded.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => 'header.@@@invalid@@@.signature',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testFetchUserClaimsThrowsOnIdTokenIssuerMismatch(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC ID token issuer mismatch.');

        $idToken = $this->createIdToken([
            'nonce' => 'nonce-123',
            'iss' => 'https://evil.example.com',
            'aud' => 'my-client-id',
            'exp' => time() + 3600,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'access_token' => 'the-access-token',
                'id_token' => $idToken,
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient);
        $sut->fetchUserClaims('the-code', 'https://kimai.local/oidc/callback', 'nonce-123');
    }

    public function testGetAuthorizationUrlThrowsOnDiscoveryErrorStatus(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC discovery request failed.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode(['message' => 'down']), [
                'http_code' => 500,
                'response_headers' => ['content-type' => 'application/json'],
            ]),
        ]);

        $sut = $this->getSut($httpClient, ['authorization_url' => '']);
        $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state-123', 'nonce-123');
    }

    public function testGetAuthorizationUrlThrowsWhenDiscoveryEndpointMissing(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC discovery document is missing the "authorization_endpoint" endpoint.');

        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'issuer' => 'https://id.example.com',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient, ['authorization_url' => '']);
        $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state-123', 'nonce-123');
    }

    public function testGetAuthorizationUrlCachesDiscoveryDocument(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode([
                'issuer' => 'https://id.example.com',
                'authorization_endpoint' => 'https://id.example.com/authorize',
            ]), ['response_headers' => ['content-type' => 'application/json']]),
        ]);

        $sut = $this->getSut($httpClient, ['authorization_url' => '']);

        $first = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state-123', 'nonce-123');
        $second = $sut->getAuthorizationUrl('https://kimai.local/oidc/callback', 'state-456', 'nonce-456');

        self::assertStringStartsWith('https://id.example.com/authorize?', $first);
        self::assertStringStartsWith('https://id.example.com/authorize?', $second);
        // the discovery document must only be fetched once and then served from the cache
        self::assertSame(1, $httpClient->getRequestsCount());
    }
}
