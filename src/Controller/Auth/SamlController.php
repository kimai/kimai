<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Auth;

use App\Configuration\SamlConfigurationInterface;
use App\Saml\SamlAuthFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/saml')]
final class SamlController extends AbstractController
{
    public function __construct(private SamlAuthFactory $authFactory, private SamlConfigurationInterface $samlConfiguration)
    {
    }

    #[Route(path: '/login', name: 'saml_login')]
    public function loginAction(Request $request): Response
    {
        if (!$this->samlConfiguration->isActivated()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        $session = $request->getSession();
        $authErrorKey = Security::AUTHENTICATION_ERROR;

        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif ($session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if ($error) {
            if (\is_object($error) && method_exists($error, 'getMessage')) {
                $error = $error->getMessage();
            }
            throw new \RuntimeException($error);
        }

        // this does set headers and exit as $stay is not set to true
        $url = $this->authFactory->create()->login($session->get('_security.main.target_path'));

        if ($url === null) {
            throw new \RuntimeException('SAML login failed');
        }

        // this line is not (yet) reached, as the previous call will exit
        return $this->redirect($url);
    }

    #[Route(path: '/metadata', name: 'saml_metadata')]
    public function metadataAction(): Response
    {
        if (!$this->samlConfiguration->isActivated()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        $metadata = $this->authFactory->create()->getSettings()->getSPMetadata();

        $response = new Response($metadata);
        $response->headers->set('Content-Type', 'xml');

        return $response;
    }

    #[Route(path: '/acs', name: 'saml_acs')]
    public function assertionConsumerServiceAction(): Response
    {
        if (!$this->samlConfiguration->isActivated()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        throw new \RuntimeException('You must configure the check path in your firewall.');
    }

    #[Route(path: '/logout', name: 'saml_logout')]
    public function logoutAction(): Response
    {
        if (!$this->samlConfiguration->isActivated()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        throw new \RuntimeException('You must configure the logout path in your firewall.');
    }
}
