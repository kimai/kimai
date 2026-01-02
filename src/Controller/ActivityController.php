<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Activity\ActivityService;
use App\Activity\ActivityStatisticService;
use App\Configuration\SystemConfiguration;
use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\Project;
use App\Event\ActivityDetailControllerEvent;
use App\Event\ActivityMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Form\ActivityEditForm;
use App\Form\ActivityRateForm;
use App\Form\ActivityTeamPermissionForm;
use App\Form\Toolbar\ActivityToolbarForm;
use App\Form\Type\ActivityType;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\TeamQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TeamRepository;
use App\User\TeamService;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage activities.
 */
#[Route(path: '/admin/activity')]
final class ActivityController extends AbstractController
{
    public function __construct(private readonly ActivityRepository $repository)
    {
    }

    #[Route(path: '/', defaults: ['page' => 1], name: 'admin_activity', methods: ['GET'])]
    #[Route(path: '/page/{page}', requirements: ['page' => '[1-9]\d*'], name: 'admin_activity_paginated', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('listing', 'activity')"))]
    public function indexAction(int $page, Request $request, EventDispatcherInterface $dispatcher, SystemConfiguration $configuration): Response
    {
        $query = new ActivityQuery();
        $query->loadTeams();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('admin_activity');
        }

        $entries = $this->repository->getPagerfantaForQuery($query);

        $event = new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::ACTIVITY);
        $dispatcher->dispatch($event);
        $metaColumns = $event->getFields();

        $table = new DataTable('activity_admin', $query);
        $table->setPagination($entries);
        $table->setSearchForm($form);
        $table->setPaginationRoute('admin_activity_paginated');
        $table->setReloadEvents('kimai.activityUpdate kimai.activityDelete kimai.activityTeamUpdate');

        $table->addColumn('name', ['class' => 'alwaysVisible']);
        $table->addColumn('project', ['class' => 'd-none']);
        $table->addColumn('comment', ['class' => 'd-none', 'title' => 'description']);
        $table->addColumn('number', ['class' => 'd-none w-min', 'title' => 'activity_number']);

        foreach ($metaColumns as $metaColumn) {
            $table->addColumn('mf_' . $metaColumn->getName(), ['title' => $metaColumn->getLabel(), 'class' => 'd-none', 'orderBy' => false, 'data' => $metaColumn]);
        }

        if ($this->isGranted('budget_money', 'activity')) {
            $table->addColumn('budget', ['class' => 'd-none text-end w-min', 'title' => 'budget']);
        }

        if ($this->isGranted('budget_time', 'activity')) {
            $table->addColumn('timeBudget', ['class' => 'd-none text-end w-min', 'title' => 'timeBudget']);
        }

        $table->addColumn('billable', ['class' => 'd-none text-center w-min', 'orderBy' => false]);
        $table->addColumn('team', ['class' => 'text-center w-min', 'orderBy' => false]);
        $table->addColumn('visible', ['class' => 'd-none text-center w-min']);
        $table->addColumn('actions', ['class' => 'actions']);

        $page = $this->createPageSetup();
        $page->setDataTable($table);
        $page->setActionName('activities');

        return $this->render('activity/index.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
            'metaColumns' => $metaColumns,
            'defaultCurrency' => $configuration->getDefaultCurrency(),
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }

    #[Route(path: '/{id}/details', name: 'activity_details', methods: ['GET', 'POST'])]
    #[IsGranted('view', 'activity')]
    public function detailsAction(Activity $activity, TeamRepository $teamRepository, ActivityRateRepository $rateRepository, ActivityStatisticService $statisticService, ActivityService $activityService, EventDispatcherInterface $dispatcher): Response
    {
        $activityService->loadMetaFields($activity);

        $stats = null;
        $rates = [];
        $teams = null;
        $defaultTeam = null;
        $now = $this->getDateTimeFactory()->createDateTime();

        $exportUrl = null;
        $invoiceUrl = null;
        $params = ['customers[]' => '', 'projects[]' => '', 'activities[]' => $activity->getId(), 'daterange' => '', 'exported' => TimesheetQuery::STATE_NOT_EXPORTED, 'billable' => true];
        if ($activity->getProject() !== null) {
            $params['projects[]'] = $activity->getProject()->getId();
            if ($activity->getProject()->getCustomer() !== null) {
                $params['customers[]'] = $activity->getProject()->getCustomer()->getId();
            }
        }
        if ($this->isGranted('create_export')) {
            $exportUrl = $this->generateUrl('export', array_merge($params, ['preview' => true]));
        }
        if ($this->isGranted('view_invoice')) {
            $invoiceUrl = $this->generateUrl('invoice', $params);
        }

        if ($this->isGranted('edit', $activity)) {
            if ($this->isGranted('create_team')) {
                $defaultTeam = $teamRepository->findOneBy(['name' => $activity->getName()]);
            }
            $rates = $rateRepository->getRatesForActivity($activity);
        }

        if ($this->isGranted('budget', $activity) || $this->isGranted('time', $activity)) {
            $stats = $statisticService->getBudgetStatisticModel($activity, $now);
        }

        if ($this->isGranted('permissions', $activity) || $this->isGranted('details', $activity) || $this->isGranted('view_team')) {
            $query = new TeamQuery();
            $query->addActivity($activity);
            $teams = $teamRepository->getTeamsForQuery($query);
        }

        // additional boxes by plugins
        $event = new ActivityDetailControllerEvent($activity);
        $dispatcher->dispatch($event);
        $boxes = $event->getController();

        $page = $this->createPageSetup();
        $page->setActionName('activity');
        $page->setActionView('activity_details');
        $page->setActionPayload(['activity' => $activity]);

        return $this->render('activity/details.html.twig', [
            'page_setup' => $page,
            'activity' => $activity,
            'stats' => $stats,
            'rates' => $rates,
            'team' => $defaultTeam,
            'teams' => $teams,
            'now' => $now,
            'boxes' => $boxes,
            'export_url' => $exportUrl,
            'invoice_url' => $invoiceUrl,
        ]);
    }

    #[Route(path: '/{id}/rate/{rate}', name: 'admin_activity_rate_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'activity')]
    public function editRateAction(Activity $activity, ActivityRate $rate, Request $request, ActivityRateRepository $repository): Response
    {
        return $this->rateFormAction($activity, $rate, $request, $repository, $this->generateUrl('admin_activity_rate_edit', ['id' => $activity->getId(), 'rate' => $rate->getId()]));
    }

    #[Route(path: '/{id}/rate', name: 'admin_activity_rate_add', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'activity')]
    public function addRateAction(Activity $activity, Request $request, ActivityRateRepository $repository): Response
    {
        $rate = new ActivityRate();
        $rate->setActivity($activity);

        return $this->rateFormAction($activity, $rate, $request, $repository, $this->generateUrl('admin_activity_rate_add', ['id' => $activity->getId()]));
    }

    private function rateFormAction(Activity $activity, ActivityRate $rate, Request $request, ActivityRateRepository $repository, string $formUrl): Response
    {
        $form = $this->createForm(ActivityRateForm::class, $rate, [
            'action' => $formUrl,
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repository->saveRate($rate);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('activity/rates.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/create/{project}', name: 'admin_activity_create_with_project', methods: ['GET', 'POST'])]
    #[IsGranted('create_activity')]
    public function createWithProjectAction(Project $project, Request $request, ActivityService $activityService, SystemConfiguration $configuration): Response
    {
        return $this->createActivity($request, $activityService, $configuration, $project);
    }

    #[Route(path: '/create', name: 'admin_activity_create', methods: ['GET', 'POST'])]
    #[IsGranted('create_activity')]
    public function createAction(Request $request, ActivityService $activityService, SystemConfiguration $configuration): Response
    {
        return $this->createActivity($request, $activityService, $configuration, null);
    }

    private function createActivity(Request $request, ActivityService $activityService, SystemConfiguration $configuration, ?Project $project = null): Response
    {
        $activity = $activityService->createNewActivity($project);

        $editForm = $this->createEditForm($activity, $configuration);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $activityService->saveActivity($activity);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRouteAfterCreate('activity_details', ['id' => $activity->getId()]);
            } catch (Exception $ex) {
                $this->handleFormUpdateException($ex, $editForm);
            }
        }

        return $this->render('activity/edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'activity' => $activity,
            'form' => $editForm->createView()
        ]);
    }

    #[Route(path: '/{id}/permissions', name: 'admin_activity_permissions', methods: ['GET', 'POST'])]
    #[IsGranted('permissions', 'activity')]
    public function teamPermissionsAction(Activity $activity, Request $request, ActivityService $activityService): Response
    {
        $form = $this->createForm(ActivityTeamPermissionForm::class, $activity, [
            'action' => $this->generateUrl('admin_activity_permissions', ['id' => $activity->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $activityService->saveActivity($activity);
                $this->flashSuccess('action.update.success');

                if ($this->isGranted('view', $activity)) {
                    return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
                }

                return $this->redirectToRoute('admin_activity');
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('activity/permissions.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/create_team', name: 'activity_team_create', methods: ['GET'])]
    #[IsGranted('create_team')]
    #[IsGranted('permissions', 'activity')]
    public function createDefaultTeamAction(Activity $activity, TeamService $teamService): Response
    {
        $name = $activity->getName();
        if ($name === null) {
            throw new BadRequestHttpException('Cannot create default team for activity with empty name: ' . $activity->getId());
        }

        $defaultTeam = $teamService->findTeamByName($name);

        if (null === $defaultTeam) {
            $defaultTeam = $teamService->createNewTeam($name);
        }

        $defaultTeam->addTeamlead($this->getUser());
        $defaultTeam->addActivity($activity);

        try {
            $teamService->saveTeam($defaultTeam);
        } catch (Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
    }

    #[Route(path: '/{id}/edit', name: 'admin_activity_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'activity')]
    public function editAction(Activity $activity, Request $request, ActivityService $activityService, SystemConfiguration $configuration): Response
    {
        $activityService->loadMetaFields($activity);

        $editForm = $this->createEditForm($activity, $configuration);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $activityService->saveActivity($activity);
                $this->flashSuccess('action.update.success');

                if ($this->isGranted('view', $activity)) {
                    return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
                } else {
                    return new Response();
                }
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('activity/edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'activity' => $activity,
            'form' => $editForm->createView()
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'admin_activity_delete', methods: ['GET', 'POST'])]
    #[IsGranted('delete', 'activity')]
    public function deleteAction(Activity $activity, Request $request, ActivityStatisticService $statisticService, ActivityService $activityService): Response
    {
        $stats = $statisticService->getActivityStatistics($activity);

        $options = [
            'projects' => $activity->getProject(),
            'query_builder_for_user' => true,
            'ignore_activity' => $activity,
            'required' => false,
        ];

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.activityDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('activity', ActivityType::class, $options)
            ->setAction($this->generateUrl('admin_activity_delete', ['id' => $activity->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                /** @var Activity|null $replace */
                $replace = $deleteForm->get('activity')->getData();
                $activityService->deleteActivity($activity, $replace);
                $this->flashSuccess('action.delete.success');
            } catch (Exception $ex) {
                $this->flashDeleteException($ex);
            }

            return $this->redirectToRoute('admin_activity');
        }

        return $this->render('activity/delete.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'activity' => $activity,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    #[Route(path: '/export', name: 'activity_export', methods: ['GET'])]
    #[IsGranted(new Expression("is_granted('listing', 'activity')"))]
    public function exportAction(Request $request, EntityWithMetaFieldsExporter $exporter): Response
    {
        $query = new ActivityQuery();
        $query->setCurrentUser($this->getUser());

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        $entries = $this->repository->getActivitiesForQuery($query);

        $spreadsheet = $exporter->export(
            Activity::class,
            $entries,
            new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::EXPORT)
        );
        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-activities');

        return $writer->getFileResponse($spreadsheet);
    }

    /**
     * @return FormInterface<ActivityQuery>
     */
    private function getToolbarForm(ActivityQuery $query): FormInterface
    {
        return $this->createSearchForm(ActivityToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_activity', [
                'page' => $query->getPage(),
            ])
        ]);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createEditForm(Activity $activity, SystemConfiguration $configuration): FormInterface
    {
        $currency = $configuration->getDefaultCurrency();
        $url = $this->generateUrl('admin_activity_create');
        if ($activity->getProject()?->getId() !== null) {
            $url = $this->generateUrl('admin_activity_create_with_project', ['project' => $activity->getProject()->getId()]);
        }

        if ($activity->getId() !== null) {
            $url = $this->generateUrl('admin_activity_edit', ['id' => $activity->getId()]);
        }

        if (null !== $activity->getProject()) {
            $currency = $activity->getProject()->getCustomer()->getCurrency();
        }

        return $this->createForm(ActivityEditForm::class, $activity, [
            'action' => $url,
            'method' => 'POST',
            'currency' => $currency,
            'include_budget' => $this->isGranted('budget', $activity),
            'include_time' => $this->isGranted('time', $activity),
        ]);
    }

    private function createPageSetup(): PageSetup
    {
        $page = new PageSetup('activities');
        $page->setHelp('activity.html');

        return $page;
    }
}
