<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Controller;

use OneLogin\Saml2\Auth;
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
    /**
     * @var Auth
     */
    private $oneLoginAuth;

    public function __construct(Auth $oneLoginAuth)
    {
        $this->oneLoginAuth = $oneLoginAuth;
    }

    /**
     * @Route(path="/login", name="saml_login")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();
        $authErrorKey = Security::AUTHENTICATION_ERROR;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if ($error) {
            throw new \RuntimeException($error->getMessage());
        }

        $this->oneLoginAuth->login($session->get('_security.main.target_path'));
    }

    /**
     * @Route(path="/metadata", name="saml_metadata")
     */
    public function metadataAction()
    {
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
        throw new \RuntimeException('You must configure the check path in your firewall.');
    }

    /**
     * @Route(path="/logout", name="saml_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must configure the logout path in your firewall.');
    }
}
