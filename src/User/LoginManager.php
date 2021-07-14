<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User;

use App\Entity\User;
use App\Event\UserInteractiveLoginEvent;
use App\Security\UserChecker;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoginManager
{
    private $tokenStorage;
    private $userChecker;
    private $sessionStrategy;
    private $requestStack;
    private $eventDispatcher;
    private $rememberMeService;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserChecker $userChecker,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        RememberMeServicesInterface $rememberMeService = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userChecker = $userChecker;
        $this->sessionStrategy = $sessionStrategy;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->rememberMeService = $rememberMeService;
    }

    public function logInUser(User $user, Response $response = null)
    {
        $this->userChecker->checkPreAuth($user);

        $token = $this->createToken('secured_area', $user);
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request) {
            $this->sessionStrategy->onAuthentication($request, $token);

            if (null !== $response && null !== $this->rememberMeService) {
                $this->rememberMeService->loginSuccess($request, $response, $token);
            }
        }

        $this->tokenStorage->setToken($token);

        $this->eventDispatcher->dispatch(new UserInteractiveLoginEvent($user));
    }

    private function createToken(string $firewall, User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
    }
}
