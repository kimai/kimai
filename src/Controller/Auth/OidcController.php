<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Auth;

use App\Configuration\OidcConfigurationInterface;
use App\Oidc\OidcAuthenticator;
use App\Oidc\OidcClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/oidc')]
final class OidcController extends AbstractController
{
    public function __construct(
        private readonly OidcClient $oidcClient,
        private readonly OidcConfigurationInterface $configuration,
    ) {
    }

    #[Route(path: '/login', name: 'oidc_login')]
    public function loginAction(Request $request): Response
    {
        if (!$this->configuration->isActivated()) {
            throw $this->createNotFoundException('OIDC deactivated');
        }

        $state = bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        $codeVerifier = bin2hex(random_bytes(32));

        $session = $request->getSession();
        $session->set(OidcAuthenticator::SESSION_STATE, $state);
        $session->set(OidcAuthenticator::SESSION_NONCE, $nonce);
        $session->set(OidcAuthenticator::SESSION_PKCE, $codeVerifier);

        $redirectUri = $this->generateUrl('oidc_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $url = $this->oidcClient->getAuthorizationUrl($redirectUri, $state, $nonce, $codeVerifier);

        return new RedirectResponse($url);
    }

    #[Route(path: '/callback', name: 'oidc_callback')]
    public function callbackAction(): Response
    {
        if (!$this->configuration->isActivated()) {
            throw $this->createNotFoundException('OIDC deactivated');
        }

        // this route is intercepted by the OidcAuthenticator configured in the firewall
        throw new \RuntimeException('You must configure the check path to be handled by the firewall.');
    }
}
