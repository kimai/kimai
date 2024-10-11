<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Security;

use App\Configuration\SamlConfigurationInterface;
use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    public function __construct(private CsrfTokenManagerInterface $tokenManager, private SamlConfigurationInterface $samlConfiguration)
    {
    }

    #[Route(path: '/login', name: 'login', methods: ['GET', 'POST'])]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $csrfToken = $this->tokenManager->getToken('authenticate')->getValue();

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED') && $this->getUser()->isInternalUser()) {
            return $this->render('security/unlock.html.twig', [
                'error' => $error,
                'csrf_token' => $csrfToken,
            ]);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
            'saml_config' => $this->samlConfiguration,
        ]);
    }

    #[Route(path: '/login_check', name: 'security_check', methods: ['POST'])]
    public function checkAction(): Response
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    #[Route(path: '/logout', name: 'logout', methods: ['GET', 'POST'])]
    public function logoutAction(): Response
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
