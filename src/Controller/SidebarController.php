<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\UserPreferencesForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Sidebar controller
 *
 * @Security("has_role('ROLE_USER')")
 */
class SidebarController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction()
    {
        return $this->render('sidebar/home.html.twig', []);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction()
    {
        $user = $this->getUser();

        return $this->render('sidebar/settings.html.twig', [
            'user' => $user,
        ]);

        /*

        $form = $this->createForm(UserPreferencesForm::class, $user, [
            'action' => $this->generateUrl('user_profile_preferences', ['username' => $user->getUsername()]),
            'method' => 'POST',
        ]);

        return $this->render('sidebar/settings.html.twig', [
            'form' => $form->createView(),
        ]);
        */
    }
}
