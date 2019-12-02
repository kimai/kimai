<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Event\TimesheetMetaDisplayEvent;
use App\Form\TimesheetAdminEditForm;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @return Response
     */
    public function indexAction($page, Request $request)
    {
        return $this->index($page, $request, 'timesheet-team/index.html.twig', TimesheetMetaDisplayEvent::TEAM_TIMESHEET);
    }

    /**
     * @Route(path="/export/{exporter}", name="admin_timesheet_export", methods={"GET"})
     *
     * @param Request $request
     * @param string $exporter
     * @return Response
     */
    public function exportAction(Request $request, string $exporter)
    {
        return $this->export($request, $exporter);
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
    public function createAction(Request $request, ProjectRepository $projectRepository, ActivityRepository $activityRepository, TagRepository $tagRepository)
    {
        return $this->create($request, 'timesheet-team/edit.html.twig', $projectRepository, $activityRepository, $tagRepository);
    }

    /**
     * @Route(path="/multi-update", name="admin_timesheet_multi_update", methods={"POST"})
     * @Security("is_granted('edit_other_timesheet')")
     */
    public function multiUpdateAction(Request $request)
    {
        return $this->multiUpdate($request, 'timesheet-team/multi-update.html.twig');
    }

    /**
     * @Route(path="/multi-delete", name="admin_timesheet_multi_delete", methods={"POST"})
     * @Security("is_granted('delete_other_timesheet')")
     */
    public function multiDeleteAction(Request $request)
    {
        return $this->multiDelete($request);
    }

    protected function prepareQuery(TimesheetQuery $query)
    {
        $query->setCurrentUser($this->getUser());
    }

    protected function getPermissionEditExport(): string
    {
        return 'edit_export_other_timesheet';
    }

    protected function getCreateFormClassName(): string
    {
        return TimesheetAdminEditForm::class;
    }

    protected function getEditFormClassName(): string
    {
        return TimesheetAdminEditForm::class;
    }

    protected function includeUserInForms(string $formName): bool
    {
        if ($formName === 'toolbar') {
            return true;
        }

        return $this->isGranted('edit_other_timesheet');
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

    protected function getMultiUpdateRoute(): string
    {
        return 'admin_timesheet_multi_update';
    }

    protected function getMultiDeleteRoute(): string
    {
        return 'admin_timesheet_multi_delete';
    }

    protected function canSeeStartEndTime(): bool
    {
        return true;
    }
}
