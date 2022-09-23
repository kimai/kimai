<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Security;

use App\Configuration\SamlConfigurationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SecurityController extends AbstractController
{
    private $tokenManager;
    private $samlConfiguration;

    public function __construct(CsrfTokenManagerInterface $tokenManager, SamlConfigurationInterface $samlConfiguration)
    {
        $this->tokenManager = $tokenManager;
        $this->samlConfiguration = $samlConfiguration;
    }

    /**
     * @Route(path="/login", name="fos_user_security_login", methods={"GET", "POST"})
     */
    public function loginAction(Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('homepage');
        }

        /** @var SessionInterface $session */
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        $lastUsername = '';
        if ($request->hasSession()) {
            $lastUsername = $session->get($lastUsernameKey);
        }

        $csrfToken = $this->tokenManager->getToken('authenticate')->getValue();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
            'saml_config' => $this->samlConfiguration,
        ]);
    }

    /**
     * @Route(path="/login_check", name="fos_user_security_check", methods={"POST"})
     */
    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * @Route(path="/logout", name="fos_user_security_logout", methods={"GET", "POST"})
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
