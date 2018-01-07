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

use AppBundle\Controller\AbstractController;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use TimesheetBundle\Controller\TimesheetControllerTrait;
use TimesheetBundle\Entity\Timesheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * Controller used for manage timesheet entries in the admin part of the site.
 *
 * @Route("/team/timesheet")
 * @Security("has_role('ROLE_TEAMLEAD')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * This route shows all users timesheet entries.
     *
     * @Route("/", defaults={"page": 1}, name="admin_timesheet")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_timesheet_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     *
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page, Request $request)
    {
        $query = $this->getQueryForRequest($request);
        $query->setPage($page);

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('TimesheetBundle:admin:timesheet.html.twig', [
            'entries' => $entries,
            'page' => $page,
            'query' => $query,
            'toolbarForm' => $this->getToolbarForm($query, 'admin_timesheet')->createView(),
        ]);
    }

    /**
     * The route to stop a running entry.
     *
     * @Route("/{id}/stop", name="admin_timesheet_stop")
     * @Method({"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        try {
            $this->getRepository()->stopRecording($entry);
            $this->flashSuccess('timesheet.stop.success');
        } catch (\Exception $ex) {
            $this->flashError('timesheet.stop.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('admin_timesheet');
    }
}
