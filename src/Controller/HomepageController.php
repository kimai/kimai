<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\InitialViewType;
use App\Utils\LanguageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Homepage controller is a redirect controller with user specific logic.
 *
 * @Route(path="/homepage")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class HomepageController extends AbstractController
{
    /**
     * @Route(path="", defaults={}, name="homepage", methods={"GET"})
     */
    public function indexAction(Request $request, LanguageService $service): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userRoute = $user->getPreferenceValue('login.initial_view', InitialViewType::DEFAULT_VIEW, false);
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
        if (!$service->isKnownLanguage($userLanguage)) {
            $userLanguage = $service->getDefaultLanguage();
        }

        $routes = [
            [$userRoute, $userLanguage],
            [$userRoute, $requestLanguage],
            [$userRoute, User::DEFAULT_LANGUAGE],
            [InitialViewType::DEFAULT_VIEW, $userLanguage],
            [InitialViewType::DEFAULT_VIEW, $requestLanguage],
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

        return $this->redirectToRoute(InitialViewType::DEFAULT_VIEW, ['_locale' => User::DEFAULT_LANGUAGE]);
    }
}
