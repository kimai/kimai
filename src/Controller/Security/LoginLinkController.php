<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Security;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/link')]
/**
 * @CloudRequired
 */
final class LoginLinkController extends AbstractController
{
    #[Route(path: '/check', name: 'link_login_check', methods: ['GET'])]
    public function check(): Response
    {
        return new Response();
    }
}
