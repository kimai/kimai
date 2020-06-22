<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    public const HEADER_USERNAME = 'X-AUTH-USER';
    public const HEADER_TOKEN = 'X-AUTH-TOKEN';
    public const HEADER_JAVASCRIPT = 'X-AUTH-SESSION';

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        // API docs can only be access, when the user is logged in
        if (strpos($request->getRequestUri(), '/api/doc') !== false) {
            return false;
        }

        // only try to use this authenticator, when the URL contains the /api/ path
        if (strpos($request->getRequestUri(), '/api/') !== false) {
            // javascript requests can set a header to disable this authenticator and use the existing session
            return !$request->headers->has(self::HEADER_JAVASCRIPT);
        }

        return false;
    }

    /**
     * @param Request $request
     * @return array|bool
     */
    public function getCredentials(Request $request)
    {
        return [
            'user' => $request->headers->get(self::HEADER_USERNAME),
            'token' => $request->headers->get(self::HEADER_TOKEN),
        ];
    }

    /**
     * @param array $credentials
     * @param UserProviderInterface $userProvider
     * @return null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = $credentials['token'] ?? null;
        $user = $credentials['user'] ?? null;

        if (empty($token) || empty($user)) {
            return null;
        }

        return $userProvider->loadUserByUsername($user);
    }

    /**
     * @param array $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $token = $credentials['token'];

        if (!empty($token) && $user instanceof User && !empty($user->getApiToken())) {
            $encoder = $this->encoderFactory->getEncoder($user);

            return $encoder->isPasswordValid($user->getApiToken(), $token, $user->getSalt());
        }

        return false;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return null|Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return null|JsonResponse|Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
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

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse|Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication required, missing headers: ' . self::HEADER_USERNAME . ', ' . self::HEADER_TOKEN
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
