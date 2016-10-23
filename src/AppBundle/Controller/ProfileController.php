<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * User profile controller
 *
 * @Route("/profile")
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProfileController extends Controller
{
    /**
     * @Route("/", defaults={"ident": null}, name="user_profile")
     * @Route("/{ident}/", requirements={"ident": "[a-zA-Z0-9\-].*"}, name="user_profile_ident")
     * @Method("GET")
     */
    public function indexAction($ident)
    {
        // FIXME fetch values dynamically and add trans filter to macros
        $items = [
            [
                'title' => 'Projects',
                'url' => '#',
                'color' => 'blue',
                'amount' => 12
            ],
            [
                'title' => 'Tasks',
                'url' => '#',
                'color' => 'aqua',
                'amount' => 5
            ],
            [
                'title' => 'Projects',
                'url' => '#',
                'color' => 'red',
                'amount' => 27
            ],
            [
                'title' => 'Einträge',
                'url' => '#',
                'color' => 'green',
                'amount' => 842
            ],
        ];

        return $this->render(
            'user/profile.html.twig',
            ['user' => $this->getUser(), 'items' => $items]
        );
    }
}
