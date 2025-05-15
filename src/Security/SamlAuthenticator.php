<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Saml\SamlAuthFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class SamlAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly SamlAuthFactory $samlAuth,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'saml_acs';
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $this->samlAuth->create();
        $auth->processResponse();

        if (!$auth->isAuthenticated()) {
            throw new AuthenticationException('SAML authentication failed');
        }

        $attributes = $auth->getAttributes();
        $nameId = $auth->getNameId();
        $email = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0] ?? $nameId;

        return new SelfValidatingPassport(
            new UserBadge($email, function($email) use ($attributes) {
                $user = $this->userRepository->findOneBy(['email' => $email]);
                
                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($email);
                    $user->setEnabled(true);
                }

                // Update user attributes from SAML response
                if (isset($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/displayname'][0])) {
                    $user->setAlias($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/displayname'][0]);
                }

                // Set roles based on SAML groups
                $roles = ['ROLE_USER'];
                if (isset($attributes['http://schemas.microsoft.com/ws/2008/06/identity/claims/groups'])) {
                    foreach ($attributes['http://schemas.microsoft.com/ws/2008/06/identity/claims/groups'] as $group) {
                        if ($group === 'Kimai-Admins') {
                            $roles[] = 'ROLE_ADMIN';
                        }
                    }
                }
                $user->setRoles(array_unique($roles));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
} 