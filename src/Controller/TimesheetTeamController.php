<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\ServiceExport;
use App\Form\Model\MultiUserTimesheet;
use App\Form\TimesheetAdminEditForm;
use App\Form\TimesheetMultiUserEditForm;
use App\Repository\Query\TimesheetQuery;
use App\Utils\PageSetup;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/team/timesheet')]
#[IsGranted('view_other_timesheet')]
final class TimesheetTeamController extends TimesheetAbstractController
{
    #[Route(path: '/', defaults: ['page' => 1], name: 'admin_timesheet', methods: ['GET'])]
    #[Route(path: '/page/{page}', requirements: ['page' => '[1-9]\d*'], name: 'admin_timesheet_paginated', methods: ['GET'])]
    #[IsGranted('view_other_timesheet')]
    public function indexAction(int $page, Request $request): Response
    {
        $query = $this->createDefaultQuery();
        $query->setPage($page);

        return $this->index($query, $request, 'admin_timesheet', 'admin_timesheet_paginated', TimesheetMetaDisplayEvent::TEAM_TIMESHEET);
    }

    #[Route(path: '/export/', name: 'admin_timesheet_export', methods: ['GET', 'POST'])]
    #[IsGranted('export_other_timesheet')]
    public function exportAction(Request $request, ServiceExport $serviceExport): Response
    {
        return $this->export($request, $serviceExport);
    }

    #[Route(path: '/{id}/edit', name: 'admin_timesheet_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'entry')]
    public function editAction(Timesheet $entry, Request $request): Response
    {
        return $this->edit($entry, $request);
    }

    #[Route(path: '/{id}/duplicate', name: 'admin_timesheet_duplicate', methods: ['GET', 'POST'])]
    #[IsGranted('duplicate', 'entry')]
    public function duplicateAction(Timesheet $entry, Request $request): Response
    {
        return $this->duplicate($entry, $request);
    }

    #[Route(path: '/create', name: 'admin_timesheet_create', methods: ['GET', 'POST'])]
    #[IsGranted('create_other_timesheet')]
    public function createAction(Request $request): Response
    {
        return $this->create($request);
    }

    #[Route(path: '/create_mu', name: 'admin_timesheet_create_multiuser', methods: ['GET', 'POST'])]
    #[IsGranted('create_other_timesheet')]
    public function createForMultiUserAction(Request $request): Response
    {
        $entry = new MultiUserTimesheet();
        $entry->setUser($this->getUser());
        $this->service->prepareNewTimesheet($entry, $request);

        $createForm = $this->getMultiUserCreateForm($entry);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            try {
                /** @var ArrayCollection<User> $users */
                $users = $createForm->get('users')->getData();
                /** @var ArrayCollection<Team> $teams */
                $teams = $createForm->get('teams')->getData();

                $allUsers = $users->toArray();
                /** @var Team $team */
                foreach ($teams as $team) {
                    $allUsers = array_merge($allUsers, $team->getUsers());
                }
                $allUsers = array_unique($allUsers);

                /** @var Tag[] $tags */
                $tags = [];
                /** @var Tag $tag */
                foreach ($entry->getTags() as $tag) {
                    $entry->addTag($tag);
                    $tags[] = $tag;
                }

                foreach ($allUsers as $user) {
                    $newTimesheet = $entry->createCopy();
                    $newTimesheet->setUser($user);
                    foreach ($tags as $tag) {
                        $newTimesheet->addTag($tag);
                    }
                    $this->service->prepareNewTimesheet($newTimesheet, $request);
                    $this->service->saveNewTimesheet($newTimesheet);
                }

                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute());
            } catch (\Exception $ex) {
                // FIXME I guess this will save timesheets for some users, but then fail only for single users
                // FIXME we should run in a transaction or disallow to create running timesheets
                $this->handleFormUpdateException($ex, $createForm);
            }
        }

        return $this->render('timesheet/edit.html.twig', [
            'timesheet' => $entry,
            'form' => $createForm->createView(),
            'template' => $this->getTrackingMode()->getEditTemplate(),
        ]);
    }

    protected function getMultiUserCreateForm(MultiUserTimesheet $entry): FormInterface
    {
        $mode = $this->getTrackingMode();

        return $this->createForm(TimesheetMultiUserEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_create_multiuser'),
            'include_rate' => $this->isGranted($this->getPermissionEditRate()),
            'include_exported' => $this->isGranted($this->getPermissionEditExport()),
            'include_billable' => $this->isGranted($this->getPermissionEditBillable()),
            'include_user' => $this->includeUserInForms('create'),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'customer' => true,
        ]);
    }

    #[Route(path: '/multi-update', name: 'admin_timesheet_multi_update', methods: ['POST'])]
    #[IsGranted('edit_other_timesheet')]
    public function multiUpdateAction(Request $request): Response
    {
        return $this->multiUpdate($request);
    }

    #[Route(path: '/multi-delete', name: 'admin_timesheet_multi_delete', methods: ['POST'])]
    #[IsGranted('delete_other_timesheet')]
    public function multiDeleteAction(Request $request): Response
    {
        return $this->multiDelete($request);
    }

    protected function prepareQuery(TimesheetQuery $query)
    {
        $query->setCurrentUser($this->getUser());
    }

    protected function getCreateForm(Timesheet $entry): FormInterface
    {
        return $this->generateCreateForm($entry, TimesheetAdminEditForm::class, $this->generateUrl('admin_timesheet_create'));
    }

    protected function getDuplicateForm(Timesheet $entry, Timesheet $original): FormInterface
    {
        return $this->generateCreateForm($entry, TimesheetAdminEditForm::class, $this->generateUrl('admin_timesheet_duplicate', ['id' => $original->getId()]));
    }

    protected function getPermissionEditExport(): string
    {
        return 'edit_export_other_timesheet';
    }

    protected function getPermissionEditBillable(): string
    {
        return 'edit_billable_other_timesheet';
    }

    protected function getPermissionEditRate(): string
    {
        return 'edit_rate_other_timesheet';
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

    protected function getExportRoute(): string
    {
        return 'admin_timesheet_export';
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

    protected function getQueryNamePrefix(): string
    {
        return 'TeamTimes';
    }

    protected function canSeeRate(): bool
    {
        return $this->isGranted('view_rate_other_timesheet');
    }

    protected function canSeeUsername(): bool
    {
        return true;
    }

    protected function hasMarkdownSupport(): bool
    {
        return false;
    }

    protected function getTableName(): string
    {
        return 'timesheet_admin';
    }

    protected function getActionName(): string
    {
        return 'timesheets_team';
    }

    protected function getActionNameSingle(): string
    {
        return 'timesheet_team';
    }

    protected function createPageSetup(): PageSetup
    {
        $page = new PageSetup('all_times');
        $page->setHelp('timesheet.html');

        return $page;
    }
}
