<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\LocaleService;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Homepage controller is a redirect controller with user specific logic.
 */
#[Route(path: '/homepage')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class HomepageController extends AbstractController
{
    public const DEFAULT_ROUTE = 'timesheet';

    #[Route(path: '', defaults: [], name: 'homepage', methods: ['GET'])]
    public function indexAction(Request $request, LocaleService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userRoute = $user->getPreferenceValue('login_initial_view', self::DEFAULT_ROUTE, false);
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
            $userLanguage = $service->getDefaultLocale();
        }

        $routes = [
            [$userRoute, $userLanguage],
            [$userRoute, $requestLanguage],
            [$userRoute, User::DEFAULT_LANGUAGE],
            [self::DEFAULT_ROUTE, $userLanguage],
            [self::DEFAULT_ROUTE, $requestLanguage],
        ];

        foreach ($routes as $routeSettings) {
            $route = $routeSettings[0];
            $language = $routeSettings[1];
            try {
                return $this->redirectToRoute($route, ['_locale' => $language]);
            } catch (\Exception $ex) {
                $this->logException($ex);
                // something is wrong with the url parameters ...
            }
        }

        return $this->redirectToRoute(self::DEFAULT_ROUTE, ['_locale' => User::DEFAULT_LANGUAGE]);
    }
}
