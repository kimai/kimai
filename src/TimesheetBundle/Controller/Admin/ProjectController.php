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

use Pagerfanta\Pagerfanta;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * Controller used to manage projects in the admin part of the site.
 *
 * @Route("/admin/project")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectController extends Controller
{
    /**
     * @Route("/", defaults={"page": 1}, name="admin_project")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_project_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page)
    {
        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(Project::class)->findAll($page);

        return $this->render('TimesheetBundle:admin:project.html.twig', ['entries' => $entries]);
    }
}
