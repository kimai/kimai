<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Ldap;

use App\Configuration\LdapConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\EntryPoint\Exception\NotAnEntryPointException;

final class LdapAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    public function __construct(private AuthenticatorInterface $authenticator, private LdapConfiguration $configuration, private LoggerInterface $logger)
    {
    }

    public function supports(Request $request): bool
    {
        if (!$this->configuration->isActivated()) {
            return false;
        }

        if (!class_exists('Laminas\Ldap\Ldap')) {
            $this->logger->debug('Failed loading LDAP authenticator, missing Laminas dependency');

            return false;
        }

        return $this->authenticator->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        $passport = $this->authenticator->authenticate($request);
        $passport->addBadge(new LdapBadge());

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return $this->authenticator->createToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->authenticator->onAuthenticationFailure($request, $exception);
    }

    public function isInteractive(): bool
    {
        if ($this->authenticator instanceof InteractiveAuthenticatorInterface) {
            return $this->authenticator->isInteractive();
        }

        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if (!$this->authenticator instanceof AuthenticationEntryPointInterface) {
            throw new NotAnEntryPointException(sprintf('Decorated authenticator "%s" does not implement interface "%s".', get_debug_type($this->authenticator), AuthenticationEntryPointInterface::class));
        }

        return $this->authenticator->start($request, $authException);
    }
}
