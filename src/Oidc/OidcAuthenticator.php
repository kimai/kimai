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
use Psr\Log\LoggerInterface;
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

/**
 * @final
 */
class OidcAuthenticator extends AbstractAuthenticator
{
    public const CHECK_ROUTE = 'oidc_callback';
    public const SESSION_STATE = '_oidc_state';
    public const SESSION_NONCE = '_oidc_nonce';
    public const SESSION_PKCE = '_oidc_pkce_verifier';

    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly OidcAuthenticationSuccessHandler $successHandler,
        private readonly OidcAuthenticationFailureHandler $failureHandler,
        private readonly OidcClient $oidcClient,
        private readonly OidcProvider $oidcProvider,
        private readonly OidcConfigurationInterface $configuration,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$this->configuration->isActivated()) {
            return false;
        }

        if (!$request->isMethod(Request::METHOD_GET)) {
            return false;
        }

        if (!$this->httpUtils->checkRequestPath($request, self::CHECK_ROUTE)) {
            return false;
        }

        return $request->query->has('code') || $request->query->has('error');
    }

    public function authenticate(Request $request): Passport
    {
        $session = $request->getSession();

        $expectedState = $session->get(self::SESSION_STATE);
        $session->remove(self::SESSION_STATE);
        $nonce = $session->get(self::SESSION_NONCE);
        $session->remove(self::SESSION_NONCE);
        $codeVerifier = $session->get(self::SESSION_PKCE);
        $session->remove(self::SESSION_PKCE);

        // the IdP redirected back with an error (e.g. the user cancelled the login)
        if ($request->query->has('error')) {
            $error = (string) $request->query->get('error');
            $this->logger->warning('OIDC login failed at the identity provider: ' . $error);
            throw new AuthenticationException('OIDC login failed: ' . $error);
        }

        $givenState = $request->query->get('state');

        // CSRF protection: the state must match the value stored before the redirect
        if (!\is_string($expectedState) || $expectedState === '' || !hash_equals($expectedState, (string) $givenState)) {
            $this->logger->critical('OIDC login failed: state mismatch.');
            throw new AuthenticationException('OIDC state mismatch.');
        }

        if (!\is_string($nonce) || $nonce === '') {
            throw new AuthenticationException('OIDC nonce is missing.');
        }

        if (!\is_string($codeVerifier) || $codeVerifier === '') {
            throw new AuthenticationException('OIDC PKCE code verifier is missing.');
        }

        $code = (string) $request->query->get('code');
        $redirectUri = $this->urlGenerator->generate(self::CHECK_ROUTE, [], UrlGeneratorInterface::ABSOLUTE_URL);

        $claims = $this->oidcClient->fetchUserClaims($code, $redirectUri, $nonce, $codeVerifier);

        $usernameClaim = $this->configuration->getUsernameClaim();
        if (!\array_key_exists($usernameClaim, $claims)) {
            $message = \sprintf('Claim "%s" not found in OIDC response.', $usernameClaim);
            $this->logger->critical($message);
            throw new AuthenticationException($message);
        }

        $identifier = $claims[$usernameClaim];
        if (\is_array($identifier)) {
            $identifier = $identifier[0] ?? null;
        }
        if (!\is_string($identifier) || $identifier === '') {
            throw new AuthenticationException(\sprintf('Claim "%s" did not contain a valid user identifier.', $usernameClaim));
        }

        $loginAttributes = new OidcLoginAttributes();
        $loginAttributes->setAttributes($claims);
        $loginAttributes->setUserIdentifier($identifier);

        return new SelfValidatingPassport(
            new UserBadge($identifier, function () use ($loginAttributes) {
                return $this->oidcProvider->findUser($loginAttributes);
            }),
            [new RememberMeBadge(), new OidcBadge($loginAttributes)]
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();

        $token = new OidcToken($user, $firewallName, $user->getRoles());

        foreach ($passport->getBadges() as $badge) {
            if ($badge instanceof OidcBadge) {
                $token->setAttributes($badge->getOidcLoginAttributes()->getAttributes());
            }
        }

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }
}
