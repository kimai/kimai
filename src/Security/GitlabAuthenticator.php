<?php

namespace App\Security;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GitlabClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

// your user entity

class GitlabAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $router;
    private $config;
    private $encoder;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        FormConfiguration $config,
        UserPasswordEncoderInterface $encoder
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->config = $config;
        $this->encoder = $encoder;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'oauth_gitlab_check';
    }

    public function getCredentials(Request $request)
    {
        // this method is only called if supports() returns true

        return $this->fetchAccessToken($this->getGitlabClient());
    }

    /**
     * @return GitlabClient
     */
    private function getGitlabClient()
    {
        return $this->clientRegistry->getClient('gitlab');
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $gitlabUser = $this->getGitlabClient()
            ->fetchUserFromToken($credentials);

        // 2) do we have a matching user by email?
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $gitlabUser->getEmail()]);

        if ($user) {
            return $user;
        }

        $user = new User();
        $user->setEnabled(true);
        $user->setRoles([User::DEFAULT_ROLE]);
        $user->setTimezone($this->config->getUserDefaultTimezone());
        $user->setLanguage($this->config->getUserDefaultLanguage());

        $user->setEmail($gitlabUser->getEmail());
        $user->setUsername($gitlabUser->getUsername());

        $password = $this->encoder->encodePassword($user, $gitlabUser->getId());
        $user->setPassword($password);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('homepage');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            '/oauth/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}