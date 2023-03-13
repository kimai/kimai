<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class SessionAuthenticator extends AbstractAuthenticator
{
    public const HEADER_JAVASCRIPT = 'X-AUTH-SESSION';

    public function __construct(private TokenAuthenticator $authenticator)
    {
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $token = parent::createToken($passport, $firewallName);

        // this should not be necessary, as /api/ is excluded from 2FA process, but just to make sure this
        // authenticator never triggers 2FA, we add the attribute to the token

        // https://symfony.com/bundles/SchebTwoFactorBundle/6.x/custom_conditions.html
        $token->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);

        return $token;
    }

    public function supports(Request $request): ?bool
    {
        if (str_contains($request->getRequestUri(), '/api/')) {
            // API docs can only be access, when the user is logged in
            if (str_contains($request->getRequestUri(), '/api/doc')) {
                return false;
            }

            return !$request->headers->has(self::HEADER_JAVASCRIPT);
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        return $this->authenticator->authenticate($request);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->authenticator->onAuthenticationFailure($request, $exception);
    }
}
