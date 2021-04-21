<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Auth;

use App\Configuration\SystemConfiguration;
use App\Saml\SamlAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route(path="/saml")
 */
final class SamlController extends AbstractController
{
    private $oneLoginAuth;
    private $systemConfiguration;

    public function __construct(SamlAuth $oneLoginAuth, SystemConfiguration $systemConfiguration)
    {
        $this->oneLoginAuth = $oneLoginAuth;
        $this->systemConfiguration = $systemConfiguration;
    }

    /**
     * @Route(path="/login", name="saml_login")
     */
    public function loginAction(Request $request)
    {
        if (!$this->systemConfiguration->isSamlActive()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        $session = $request->getSession();
        $authErrorKey = Security::AUTHENTICATION_ERROR;

        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if ($error) {
            if (\is_object($error) && method_exists($error, 'getMessage')) {
                $error = $error->getMessage();
            }
            throw new \RuntimeException($error);
        }

        $this->oneLoginAuth->login($session->get('_security.main.target_path'));
    }

    /**
     * @Route(path="/metadata", name="saml_metadata")
     */
    public function metadataAction()
    {
        if (!$this->systemConfiguration->isSamlActive()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        $metadata = $this->oneLoginAuth->getSettings()->getSPMetadata();

        $response = new Response($metadata);
        $response->headers->set('Content-Type', 'xml');

        return $response;
    }

    /**
     * @Route(path="/acs", name="saml_acs")
     */
    public function assertionConsumerServiceAction()
    {
        if (!$this->systemConfiguration->isSamlActive()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        throw new \RuntimeException('You must configure the check path in your firewall.');
    }

    /**
     * @Route(path="/logout", name="saml_logout")
     */
    public function logoutAction()
    {
        if (!$this->systemConfiguration->isSamlActive()) {
            throw $this->createNotFoundException('SAML deactivated');
        }

        throw new \RuntimeException('You must configure the logout path in your firewall.');
    }
}
