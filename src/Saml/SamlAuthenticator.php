<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml;

use App\Configuration\SamlConfiguration;
use App\Saml\Security\SamlAuthenticationFailureHandler;
use App\Saml\Security\SamlAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @final
 */
class SamlAuthenticator extends AbstractAuthenticator
{
    private array $options = [
        'check_path' => 'saml_acs',
        'login_path' => 'saml_login',
        'use_attribute_friendly_name' => false,
    ];

    public function __construct(
        private HttpUtils $httpUtils,
        private SamlAuthenticationSuccessHandler $successHandler,
        private SamlAuthenticationFailureHandler $failureHandler,
        private SamlAuthFactory $samlAuthFactory,
        private SamlProvider $samlProvider,
        private SamlConfiguration $configuration
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$this->configuration->isActivated()) {
            return false;
        }

        if (!$this->httpUtils->checkRequestPath($request, $this->options['check_path'])) {
            return false;
        }

        return true;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();

        $token = new SamlToken($user, $firewallName, $user->getRoles());
        $token->setUser($user);

        foreach ($passport->getBadges() as $badge) {
            if ($badge instanceof SamlBadge) {
                $token->setAttributes($badge->getSamlLoginAttributes()->getAttributes());
            }
        }

        return $token;
    }

    public function authenticate(Request $request): Passport
    {
        $oneLoginAuth = $this->samlAuthFactory->create();

        $oneLoginAuth->processResponse();

        // $this->logger->debug('Received SAML response: ' . $oneLoginAuth->getLastResponseXML());

        if ($oneLoginAuth->getErrors()) {
            throw new AuthenticationException($oneLoginAuth->getLastErrorReason());
        }

        $attributes = [];
        if (isset($this->options['use_attribute_friendly_name']) && $this->options['use_attribute_friendly_name']) {
            $attributes = $oneLoginAuth->getAttributesWithFriendlyName();
        } else {
            $attributes = $oneLoginAuth->getAttributes();
        }
        $attributes['sessionIndex'] = $oneLoginAuth->getSessionIndex();

        $loginAttributes = new SamlLoginAttributes();
        $loginAttributes->setAttributes($attributes);

        if (isset($this->options['username_attribute'])) {
            if (!\array_key_exists($this->options['username_attribute'], $attributes)) {
                throw new \Exception(sprintf("Attribute '%s' not found in SAML data", $this->options['username_attribute']));
            }

            $username = $attributes[$this->options['username_attribute']][0];
        } else {
            $username = $oneLoginAuth->getNameId();
        }
        $loginAttributes->setUserIdentifier($username);

        $passport = new SelfValidatingPassport(
            new UserBadge($loginAttributes->getUserIdentifier(), function () use ($loginAttributes) {
                return $this->samlProvider->findUser($loginAttributes);
            }),
            [new RememberMeBadge(), new SamlBadge($loginAttributes)]
        );

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }
}
