<?php

namespace App\Controller\OAuth;

use App\Controller\AbstractController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Routing\Annotation\Route;


class OAuthController extends AbstractController
{
    private $scopes = [
        'gitlab' => ['read_user'],
        'google' => ['profile']
    ];

    /**
     * @Route(
     *     path="/{client}",
     *     name="oauth_start",
     *     requirements={ "client": "%oauth_clients%" }
     *     )
     *
     * @param ClientRegistry $clientRegistry
     * @param string $client
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectAction(ClientRegistry $clientRegistry, string $client)
    {
        return $clientRegistry
            ->getClient($client)
            ->redirect(
                $this->scopes[$client],
                []
            );
    }

    /**
     * @Route(
     *     path="/{client}/check",
     *     name="oauth_check",
     *     requirements={ "client": "%oauth_clients%" }
     *     )
     *
     */
    public function connectCheckAction(){}
}