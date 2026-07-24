<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Auth;

use App\Configuration\OidcConfigurationInterface;
use App\Oidc\OidcDiscovery;
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
        private readonly OidcDiscovery $discovery,
        private readonly OidcConfigurationInterface $oidcConfiguration,
    ) {
    }

    #[Route(path: '/login', name: 'oidc_login')]
    public function loginAction(Request $request): Response
    {
        if (!$this->oidcConfiguration->isActivated()) {
            throw $this->createNotFoundException('OIDC deactivated');
        }

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        $session = $request->getSession();
        $session->set('oidc.state', $state);
        $session->set('oidc.nonce', $nonce);

        $scopes = ['openid', 'email', 'profile'];
        if ($this->oidcConfiguration->getRolesMapping()) {
            $scopes[] = 'groups';
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $this->oidcConfiguration->getClientId(),
            'redirect_uri' => $this->generateUrl('oidc_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'nonce' => $nonce,
        ];

        $authorizationUrl = $this->discovery->getAuthorizationEndpoint();
        $separator = str_contains($authorizationUrl, '?') ? '&' : '?';

        return new RedirectResponse($authorizationUrl . $separator . http_build_query($params));
    }

    #[Route(path: '/callback', name: 'oidc_callback')]
    public function callbackAction(): Response
    {
        if (!$this->oidcConfiguration->isActivated()) {
            throw $this->createNotFoundException('OIDC deactivated');
        }

        throw new \RuntimeException('You must configure the check path in your firewall.');
    }
}
