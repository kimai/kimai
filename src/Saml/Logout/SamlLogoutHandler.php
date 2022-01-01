<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\Logout;

use App\Saml\SamlAuthFactory;
use App\Saml\Token\SamlTokenInterface;
use OneLogin\Saml2\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

final class SamlLogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var SamlAuthFactory
     */
    private $samlAuth;

    public function __construct(SamlAuthFactory $samlAuth)
    {
        $this->samlAuth = $samlAuth;
    }

    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        if (!$token instanceof SamlTokenInterface) {
            return;
        }

        $samlAuth = $this->samlAuth->create();

        try {
            $samlAuth->processSLO();
        } catch (Error $e) {
            if (!empty($samlAuth->getSLOurl())) {
                $sessionIndex = $token->hasAttribute('sessionIndex') ? $token->getAttribute('sessionIndex') : null;
                $samlAuth->logout(null, [], $token->getUsername(), $sessionIndex);
            }
        }
    }
}
