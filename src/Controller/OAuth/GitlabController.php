<?php

namespace App\Controller\OAuth;

use App\Controller\AbstractController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class GitlabController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     *
     * @Route(path="/gitlab", name="oauth_gitlab_start")
     *
     * @param ClientRegistry $clientRegistry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // will redirect to Gitlab!
        return $clientRegistry
            ->getClient('gitlab')
            ->redirect(
                [
                    'read_user',
                ],
                []
            );
    }

    /**
     * After going to Gitlab, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route(path="/gitlab/check", name="oauth_gitlab_check")
     *
     * @param Request $request
     * @param ClientRegistry $clientRegistry
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
    }
}