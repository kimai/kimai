<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\LocaleService;
use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Event\ConfigureMainMenuEvent;
use App\Repository\UserRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Homepage controller is a redirect controller with user specific logic.
 */
#[Route(path: '/homepage')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class HomepageController extends AbstractController
{
    #[Route(path: '', defaults: [], name: 'homepage', methods: ['GET'])]
    public function homepage(Request $request, LocaleService $service, EventDispatcherInterface $eventDispatcher, UserRepository $userRepository, SystemConfiguration $systemConfiguration): Response
    {
        $user = $this->getUser();
        $userLanguage = $user->getLanguage();
        $requestLanguage = $request->getLocale();

        if (empty($requestLanguage)) {
            $requestLanguage = User::DEFAULT_LANGUAGE;
        }

        if (empty($userLanguage)) {
            $userLanguage = $requestLanguage;
        }

        // if a user somehow managed to get a wrong locale into hos account (eg. an imported user from Kimai 1)
        // make sure that he will still see a beautiful page and not a 404
        if (!$service->isKnownLocale($userLanguage)) {
            $userLanguage = 'en';
        }

        $routes = [];
        $defaultRoute = $systemConfiguration->getUserDefaultHomepage();

        $userRoute = $user->getPreferenceValue('login_initial_view');
        if (\is_string($userRoute)) {
            $event = new ConfigureMainMenuEvent();
            $eventDispatcher->dispatch($event);
            $menu = $event->findById($userRoute);
            if ($menu !== null && \count($menu->getRouteArgs()) === 0 && $menu->getRoute() !== null) {
                $userRoute = $menu->getRoute();
            }
            $routes[] = [$userRoute, $userLanguage];
            $routes[] = [$userRoute, $requestLanguage];
            $routes[] = [$userRoute, User::DEFAULT_LANGUAGE];
        }

        $routes[] = [$defaultRoute, $userLanguage];
        $routes[] = [$defaultRoute, $requestLanguage];

        foreach ($routes as $routeSettings) {
            $route = $routeSettings[0];
            $language = $routeSettings[1];
            try {
                return $this->redirectToRoute($route, ['_locale' => $language]);
            } catch (\Exception $ex) {
                if ($route === $userRoute) {
                    // fix invalid routes from old plugins / versions
                    $user->setPreferenceValue('login_initial_view', 'dashboard');
                    $userRepository->saveUser($user);
                } else {
                    $this->logException($ex);
                }
            }
        }

        return $this->redirectToRoute($defaultRoute, ['_locale' => User::DEFAULT_LANGUAGE]);
    }
}
