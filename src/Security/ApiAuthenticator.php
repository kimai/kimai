<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public const HEADER_USERNAME = 'X-AUTH-USER';
    public const HEADER_TOKEN = 'X-AUTH-TOKEN';
    public const HEADER_JAVASCRIPT = 'X-AUTH-SESSION';

    public function supports(Request $request): ?bool
    {
        // API docs can only be access, when the user is logged in
        if (str_contains($request->getRequestUri(), '/api/doc')) {
            return false;
        }

        // only try to use this authenticator, when the URL contains the /api/ path
        if (str_contains($request->getRequestUri(), '/api/')) {
            // javascript requests can set a header to disable this authenticator and use the existing session
            return !$request->headers->has(self::HEADER_JAVASCRIPT);
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get(self::HEADER_TOKEN);
        if (null === $apiToken) {
            // The token header was empty, authentication fails with HTTP Status Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('Authentication required, missing token header: ' . self::HEADER_TOKEN);
        }

        $apiUser = $request->headers->get(self::HEADER_USERNAME);
        if (null === $apiUser) {
            // The user header was empty, authentication fails with HTTP Status Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('Authentication required, missing user header: ' . self::HEADER_USERNAME);
        }

        return new SelfValidatingPassport(new UserBadge($apiUser));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if (!$request->headers->has(self::HEADER_USERNAME) || !$request->headers->has(self::HEADER_TOKEN)) {
            return new JsonResponse(
                ['message' => 'Authentication required, missing headers: ' . self::HEADER_USERNAME . ', ' . self::HEADER_TOKEN],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = [
            'message' => 'Invalid credentials'

            // security measure: do not leak real reason (unknown user, invalid credentials ...)
            // you can uncomment this for debugging
            // 'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }
}
