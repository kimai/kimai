<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Controller\Admin;

use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * Controller used for manage timesheet entries in the admin part of the site.
 *
 * @Route("/admin/timesheet")
 * @Security("has_role('ROLE_CUSTOMER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetController extends Controller
{
    /**
     * @Route("/", defaults={"page": 1}, name="admin_timesheet_index")
     * @Route("/timesheet/{page}", requirements={"page": "[1-9]\d*"}, name="admin_timesheet_index_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page)
    {
        $entries = $this->getDoctrine()->getRepository(Timesheet::class)->findAll($page);

        return $this->render('TimesheetBundle:timesheet:index.html.twig', ['entries' => $entries]);
    }
}
