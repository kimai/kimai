<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Authentication;

use App\Entity\User;
use App\Repository\ApiUserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @deprecated since 2.54 - see https://www.kimai.org/en/blog/2026/removing-api-passwords
 */
final class TokenAuthenticator extends AbstractAuthenticator
{
    public const HEADER_USERNAME = 'X-AUTH-USER';
    public const HEADER_TOKEN = 'X-AUTH-TOKEN';

    public function __construct(
        private readonly ApiUserRepository $userProvider,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly RateLimiterFactory $oldApiTokensLimiter,
        private readonly RequestStack $requestStack,
    )
    {
    }

    public function supports(Request $request): bool
    {
        if (str_contains($request->getRequestUri(), '/api/')) {
            if (str_contains($request->getRequestUri(), '/api/doc')) {
                return false;
            }

            if ($request->headers->has(self::HEADER_USERNAME) && $request->headers->has(self::HEADER_TOKEN)) {
                return true;
            }
        }

        return false;
    }

    private function getCredentials(Request $request): array
    {
        $apiUser = $request->headers->get(self::HEADER_USERNAME);
        if (null === $apiUser || '' === $apiUser) {
            throw new CustomUserMessageAuthenticationException('Authentication required, missing user header: ' . self::HEADER_USERNAME);
        }

        $apiToken = $request->headers->get(self::HEADER_TOKEN);
        if (null === $apiToken || '' === $apiToken) {
            throw new CustomUserMessageAuthenticationException('Authentication required, missing token header: ' . self::HEADER_TOKEN);
        }

        return [
            'username' => $apiUser,
            'password' => $apiToken
        ];
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);

        $checkCredentials = function (?string $presentedPassword, User $user) {
            if ('' === $presentedPassword) {
                $this->rateLimitInvalidLogin();
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if (null === $user->getApiToken()) {
                $this->rateLimitInvalidLogin();
                throw new BadCredentialsException('The user has no activated API account.');
            }

            if ($this->passwordHasherFactory->getPasswordHasher($user)->verify($user->getApiToken(), $presentedPassword)) {
                return true;
            }

            $this->rateLimitInvalidLogin();
            throw new BadCredentialsException('The presented password is invalid.');
        };

        // users should really move away from this auth endpoint
        // see https://www.kimai.org/en/blog/2026/removing-api-passwords
        @trigger_error('Using deprecated API passwords, upgrade your APP to use API tokens instead.', E_USER_DEPRECATED);
        usleep(mt_rand(200000, 500000));

        $passport = new Passport(
            new UserBadge($credentials['username'], [$this, 'loadUserByIdentifier']),
            new CustomCredentials($checkCredentials, $credentials['password'])
        );

        $passport->addBadge(new ApiTokenUpgradeBadge($credentials['password'], $this->userProvider));

        return $passport;
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $user = $this->userProvider->loadUserByIdentifier($identifier);

        if ($user === null) {
            // we could use usleep(500000); to slow down potential attacks, but using a hashing makes timing attacks more difficult
            $this->passwordHasherFactory->getPasswordHasher(User::class)->verify('$2y$13$vwn35gUbbivoS75wcByBzObCNjX4vwkBihbdXQuK23HzK1R6J5WKW', uniqid());

            $this->rateLimitInvalidLogin();
        }

        return $user;
    }

    private function rateLimitInvalidLogin(): void
    {
        $limiter = $this->oldApiTokensLimiter->create($this->requestStack->getMainRequest()?->getClientIp());
        $limit = $limiter->consume();

        if (false === $limit->isAccepted()) {
            throw new BadRequestHttpException('Too many API requests with invalid username. Possible attack?');
        }
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
