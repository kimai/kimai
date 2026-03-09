<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Auth;

use App\Configuration\OidcConfigurationInterface;
use App\Oidc\OidcClientFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route(path: '/oidc')]
final class OidcController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private readonly OidcClientFactory $clientFactory,
        private readonly OidcConfigurationInterface $oidcConfiguration,
    )
    {
    }

    #[Route(path: '/login', name: 'oidc_login')]
    public function loginAction(Request $request): Response
    {
        if (!$this->oidcConfiguration->isActivated()) {
            throw $this->createNotFoundException('OIDC deactivated');
        }

        $session = $request->getSession();
        $authErrorKey = SecurityRequestAttributes::AUTHENTICATION_ERROR;

        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif ($session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if ($error !== null) {
            if (\is_object($error) && method_exists($error, 'getMessage')) {
                $error = $error->getMessage();
            }
            throw new \RuntimeException($error);
        }

        $redirectTarget = $this->generateUrl('oidc_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $client = $this->clientFactory->create();
        $client->setRedirectURL($redirectTarget);
        $client->authenticate();

        return new Response();
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
