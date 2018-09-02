<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sidebar controller
 *
 * @Security("is_granted('ROLE_USER')")
 */
class SidebarController extends AbstractController
{
    /**
     * @param Request $originalRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction(Request $originalRequest)
    {
        return $this->render('sidebar/settings.html.twig', [
            'user' => $this->getUser(),
            'originalRequest' => $originalRequest,
        ]);
    }
}
