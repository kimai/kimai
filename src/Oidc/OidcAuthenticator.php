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
    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly OidcAuthenticationSuccessHandler $successHandler,
        private readonly OidcAuthenticationFailureHandler $failureHandler,
        private readonly OidcClientFactory $oidcClientFactory,
        private readonly OidcProvider $oidcProvider,
        private readonly OidcConfigurationInterface $configuration,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$this->configuration->isActivated()) {
            return false;
        }

        // if (!$request->isMethod(Request::METHOD_POST)) {
        //     return false;
        // }

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
        $client = $this->oidcClientFactory->create();

        $success = $client->authenticate();

        if (!$success) {
            // TODO: Pass an error message
            throw new AuthenticationException();
        }

        $attributes = get_object_vars((object) $client->requestUserInfo());

        $loginAttributes = new OidcLoginAttributes();
        $loginAttributes->setAttributes($attributes);
        $loginAttributes->setUserIdentifier($attributes['email']);

        $passport = new SelfValidatingPassport(
            new UserBadge($attributes['email'], function () use ($loginAttributes) {
                return $this->oidcProvider->findUser($loginAttributes);
            }),
            [new RememberMeBadge(), new OidcBadge($loginAttributes)]
        );

        return $passport;
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
