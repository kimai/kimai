<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Sidebar controller
 *
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class SidebarController extends AbstractController
{
    public function homeAction()
    {
        return $this->render('sidebar/home.html.twig', []);
    }

    public function settingsAction()
    {
        return $this->render('sidebar/settings.html.twig', []);
    }
}
