<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @deprecated since 2.54 - see https://www.kimai.org/en/blog/2026/removing-api-passwords
 */
final class TokenAuthenticator extends AbstractAuthenticator
{
    public const HEADER_USERNAME = 'X-AUTH-USER';
    public const HEADER_TOKEN = 'X-AUTH-TOKEN';

    public function __construct(
        private readonly RateLimiterFactory $oldApiTokensLimiter,
    )
    {
    }

    public function supports(Request $request): bool
    {
        if (str_contains($request->getRequestUri(), '/api/')) {
            if ($request->headers->has(self::HEADER_USERNAME) && $request->headers->has(self::HEADER_TOKEN)) {
                $limiter = $this->oldApiTokensLimiter->create($request->getClientIp());
                $limit = $limiter->consume();

                if (false === $limit->isAccepted()) {
                    // prevent log spam
                    return false;
                }

                @trigger_error('Rejected deprecated API password. Upgrade your APP to use API tokens instead.', E_USER_DEPRECATED);
            }
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        throw new BadCredentialsException('Rejected deprecated API password. Upgrade your APP to use API tokens instead.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = [
            'message' => $exception instanceof CustomUserMessageAuthenticationException ? $exception->getMessage() : 'Invalid credentials'
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }
}
