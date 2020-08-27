<?php

namespace App\Security;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class OAuthAuthenticator extends SocialAuthenticator
{
    protected $clientRegistry;
    private $em;
    private $router;
    private $config;
    private $encoder;

    private $clientName;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        FormConfiguration $config,
        UserPasswordEncoderInterface $encoder,
        RequestStack $requestStack
    ) {

        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->config = $config;
        $this->encoder = $encoder;
        $this->clientName = $requestStack->getCurrentRequest()->get('client');
    }

    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'oauth_check';
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getClient());
    }

    /**
     * @return OAuth2Client
     */
    public function getClient()
    {
        return $this->clientRegistry->getClient($this->clientName);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $oAuthUser = $this->getClient()
            ->fetchUserFromToken($credentials);

        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $oAuthUser->getEmail()]);

        if ($user) {
            return $user;
        }

        $user = new User();
        $user->setEnabled(true);
        $user->setRoles([User::DEFAULT_ROLE]);
        $user->setTimezone($this->config->getUserDefaultTimezone());
        $user->setLanguage($this->config->getUserDefaultLanguage());

        $user->setEmail($oAuthUser->getEmail());
        $user->setUsername($oAuthUser->getUsername());

        $password = $this->encoder->encodePassword($user, $oAuthUser->getId());
        $user->setPassword($password);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetUrl = $this->router->generate('homepage');

        return new RedirectResponse($targetUrl);
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
            $this->router->generate('fos_user_security_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}