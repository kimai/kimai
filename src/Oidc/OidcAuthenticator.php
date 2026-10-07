<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Oidc;

use App\Configuration\OidcConfigurationInterface;
use App\Oidc\Security\OidcAuthenticationFailureHandler;
use App\Oidc\Security\OidcAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @final
 */
class OidcAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly OidcAuthenticationSuccessHandler $successHandler,
        private readonly OidcAuthenticationFailureHandler $failureHandler,
        private readonly OidcProvider $oidcProvider,
        private readonly OidcConfigurationInterface $configuration,
        private readonly OidcDiscovery $discovery,
        private readonly OidcUserInfoTokenHandlerFactory $userInfoHandlerFactory,
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$this->configuration->isActivated()) {
            return false;
        }

        if (!$this->httpUtils->checkRequestPath($request, 'oidc_callback')) {
            return false;
        }

        return true;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();

        $token = new OidcToken($user, $firewallName, $user->getRoles());
        $token->setUser($user);

        foreach ($passport->getBadges() as $badge) {
            if ($badge instanceof OidcBadge) {
                $token->setAttributes($badge->getOidcLoginAttributes()->getAttributes());
            }
        }

        return $token;
    }

    public function authenticate(Request $request): Passport
    {
        $session = $request->getSession();
        $expectedState = $session->get('oidc.state');
        $session->remove('oidc.state');
        $session->remove('oidc.nonce');

        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (!\is_string($code) || $code === '') {
            throw new AuthenticationException('Missing OIDC authorization code.');
        }
        if (!\is_string($expectedState) || !\is_string($state) || !hash_equals($expectedState, $state)) {
            throw new AuthenticationException('Invalid OIDC state.');
        }

        $accessToken = $this->exchangeCodeForAccessToken($code);
        $claims = $this->fetchUserClaims($accessToken);

        if (!isset($claims['email']) || !\is_string($claims['email']) || $claims['email'] === '') {
            throw new AuthenticationException('OIDC userinfo response did not contain an email claim.');
        }

        $loginAttributes = new OidcLoginAttributes();
        $loginAttributes->setAttributes($claims);
        $loginAttributes->setUserIdentifier($claims['email']);

        return new SelfValidatingPassport(
            new UserBadge($claims['email'], fn () => $this->oidcProvider->findUser($loginAttributes)),
            [new RememberMeBadge(), new OidcBadge($loginAttributes)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    private function exchangeCodeForAccessToken(string $code): string
    {
        try {
            $response = $this->httpClient->request('POST', $this->discovery->getTokenEndpoint(), [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->urlGenerator->generate('oidc_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'client_id' => $this->configuration->getClientId(),
                    'client_secret' => $this->configuration->getClientSecret(),
                ],
            ]);
            $tokens = $response->toArray();
        } catch (\Throwable $e) {
            throw new AuthenticationException('OIDC token exchange failed: ' . $e->getMessage(), 0, $e);
        }

        if (!isset($tokens['access_token']) || !\is_string($tokens['access_token']) || $tokens['access_token'] === '') {
            throw new AuthenticationException('OIDC token response did not contain an access_token.');
        }

        return $tokens['access_token'];
    }

    private function fetchUserClaims(string $accessToken): array
    {
        $userBadge = $this->userInfoHandlerFactory->create()->getUserBadgeFrom($accessToken);

        return $userBadge->getAttributes() ?? [];
    }
}
