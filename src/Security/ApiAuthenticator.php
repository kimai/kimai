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
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiAuthenticator extends AbstractGuardAuthenticator
{
    public const HEADER_JAVASCRIPT = 'X-AUTH-SESSION';

    private $authenticator;

    public function __construct(TokenAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
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
        return $this->authenticator->getCredentials($request);
    }

    /**
     * @param array $credentials
     * @param UserProviderInterface $userProvider
     * @return null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->authenticator->getUser($credentials, $userProvider);
    }

    /**
     * @param array $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->authenticator->checkCredentials($credentials, $user);
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return null|Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return $this->authenticator->onAuthenticationSuccess($request, $token, $providerKey);
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return null|JsonResponse|Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->authenticator->onAuthenticationFailure($request, $exception);
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return JsonResponse|Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->authenticator->start($request, $authException);
    }

    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
