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
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route(path: '/saml')]
final class SamlController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private readonly SamlAuthFactory $authFactory,
        private readonly SamlConfigurationInterface $samlConfiguration,
        private readonly Security $security,
    )
    {
    }

    #[Route(path: '/login', name: 'saml_login')]
    public function loginAction(Request $request): Response
    {
        if (!$this->samlConfiguration->isActivated()) {
            throw $this->createNotFoundException('SAML deactivated');
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

        $firewallName = $this->security->getFirewallConfig($request)?->getName();
        if ($firewallName === null || $firewallName === '') {
            throw new ServiceUnavailableHttpException(message: 'Unknown firewall.');
        }

        $redirectTarget = $this->getTargetPath($session, $firewallName);
        if ($redirectTarget === null || $redirectTarget === '') {
            $redirectTarget = $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $url = $this->authFactory->create()->login($redirectTarget, [], false, false, true);

        if ($url === null) {
            throw new \RuntimeException('SAML login failed');
        }

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
