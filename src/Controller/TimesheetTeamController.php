<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/team/timesheet")
 * @Security("is_granted('view_other_timesheet')")
 */
class TimesheetTeamController extends TimesheetAbstractController
{
    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_timesheet", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_timesheet_paginated", methods={"GET"})
     * @Security("is_granted('view_other_timesheet')")
     *
     * @param int $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page, Request $request)
    {
        return $this->index($page, $request, 'timesheet-team/index.html.twig');
    }

    /**
     * @Route(path="/export", name="admin_timesheet_export", methods={"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request)
    {
        return $this->export($request, 'timesheet-team/export.html.twig');
    }

    /**
     * @Route(path="/{id}/edit", name="admin_timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        return $this->edit($entry, $request, 'timesheet-team/edit.html.twig');
    }

    /**
     * @Route(path="/create", name="admin_timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_other_timesheet')")
     *
     * @param Request $request
     * @param ProjectRepository $projectRepository
     * @param ActivityRepository $activityRepository
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request, ProjectRepository $projectRepository, ActivityRepository $activityRepository)
    {
        return $this->create($request, 'timesheet-team/edit.html.twig', $projectRepository, $activityRepository);
    }

    protected function includeUserInForms(): bool
    {
        return true;
    }

    protected function getTimesheetRoute(): string
    {
        return 'admin_timesheet';
    }

    protected function getEditRoute(): string
    {
        return 'admin_timesheet_edit';
    }

    protected function getCreateRoute(): string
    {
        return 'admin_timesheet_create';
    }
}
