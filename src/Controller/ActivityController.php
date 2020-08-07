<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\FormConfiguration;
use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Project;
use App\Entity\Team;
use App\Event\ActivityMetaDefinitionEvent;
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
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\ActivityQuery;
use App\Repository\TeamRepository;
use Exception;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route(path="/admin/activity")
 * @Security("is_granted('view_activity') or is_granted('view_teamlead_activity') or is_granted('view_team_activity')")
 */
final class ActivityController extends AbstractController
{
    /**
     * @var ActivityRepository
     */
    private $repository;
    /**
     * @var FormConfiguration
     */
    private $configuration;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(ActivityRepository $repository, FormConfiguration $configuration, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_activity", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_activity_paginated", methods={"GET"})
     */
    public function indexAction($page, Request $request)
    {
        $query = new ActivityQuery();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        /* @var $entries Pagerfanta */
        $entries = $this->repository->getPagerfantaForQuery($query);

        return $this->render('activity/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'metaColumns' => $this->findMetaColumns($query),
        ]);
    }

    /**
     * @param ActivityQuery $query
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(ActivityQuery $query): array
    {
        $event = new ActivityMetaDisplayEvent($query, ActivityMetaDisplayEvent::ACTIVITY);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    /**
     * @Route(path="/{id}/details", name="activity_details", methods={"GET", "POST"})
     * @Security("is_granted('view', activity)")
     */
    public function detailsAction(Activity $activity, TeamRepository $teamRepository, ActivityRateRepository $rateRepository)
    {
        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $stats = null;
        $rates = [];
        $teams = null;
        $defaultTeam = null;

        if ($this->isGranted('edit', $activity)) {
            if ($this->isGranted('create_team')) {
                $defaultTeam = $teamRepository->findOneBy(['name' => $activity->getName()]);
            }
            $rates = $rateRepository->getRatesForActivity($activity);
        }

        if ($this->isGranted('budget', $activity)) {
            $stats = $this->repository->getActivityStatistics($activity);
        }

        if ($this->isGranted('permissions', $activity) || $this->isGranted('details', $activity) || $this->isGranted('view_team')) {
            $teams = $activity->getTeams();
        }

        return $this->render('activity/details.html.twig', [
            'activity' => $activity,
            'stats' => $stats,
            'rates' => $rates,
            'team' => $defaultTeam,
            'teams' => $teams,
        ]);
    }

    /**
     * @Route(path="/{id}/rate", name="admin_activity_rate_add", methods={"GET", "POST"})
     * @Security("is_granted('edit', activity)")
     */
    public function addRateAction(Activity $activity, Request $request, ActivityRateRepository $repository)
    {
        $rate = new ActivityRate();
        $rate->setActivity($activity);

        $form = $this->createForm(ActivityRateForm::class, $rate, [
            'action' => $this->generateUrl('admin_activity_rate_add', ['id' => $activity->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repository->saveRate($rate);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
            } catch (Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('activity/rates.html.twig', [
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/create", name="admin_activity_create", methods={"GET", "POST"})
     * @Route(path="/create/{project}", name="admin_activity_create_with_project", methods={"GET", "POST"})
     * @Security("is_granted('create_activity')")
     */
    public function createAction(Request $request, ?Project $project = null)
    {
        $activity = new Activity();
        if (null !== $project) {
            $activity->setProject($project);
        }

        return $this->renderActivityForm($activity, $request);
    }

    /**
     * @Route(path="/{id}/permissions", name="admin_activity_permissions", methods={"GET", "POST"})
     * @Security("is_granted('permissions', activity)")
     */
    public function teamPermissionsAction(Activity $activity, Request $request)
    {
        $form = $this->createForm(ActivityTeamPermissionForm::class, $activity, [
            'action' => $this->generateUrl('admin_activity_permissions', ['id' => $activity->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->repository->saveActivity($activity);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_activity');
            } catch (Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('activity/permissions.html.twig', [
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/{id}/create_team", name="activity_team_create", methods={"GET"})
     * @Security("is_granted('create_team') and is_granted('permissions', activity)")
     */
    public function createDefaultTeamAction(Activity $activity, TeamRepository $teamRepository)
    {
        $defaultTeam = $teamRepository->findOneBy(['name' => $activity->getName()]);
        if (null !== $defaultTeam) {
            $this->flashError('action.update.error', ['%reason%' => 'Team already existing']);

            return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
        }

        $defaultTeam = new Team();
        $defaultTeam->setName($activity->getName());
        $defaultTeam->setTeamLead($this->getUser());
        $defaultTeam->addActivity($activity);

        try {
            $teamRepository->saveTeam($defaultTeam);
        } catch (Exception $ex) {
            $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('activity_details', ['id' => $activity->getId()]);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_activity_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', activity)")
     */
    public function editAction(Activity $activity, Request $request)
    {
        return $this->renderActivityForm($activity, $request);
    }

    /**
     * @Route(path="/{id}/delete", name="admin_activity_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', activity)")
     */
    public function deleteAction(Activity $activity, Request $request)
    {
        $stats = $this->repository->getActivityStatistics($activity);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.activityUpdate kimai.activityDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('activity', ActivityType::class, [
                'label' => 'label.activity',
                'query_builder' => function (ActivityRepository $repo) use ($activity) {
                    $query = new ActivityFormTypeQuery();
                    $query->addProject($activity->getProject());
                    $query->setActivityToIgnore($activity);

                    return $repo->getQueryBuilderForFormType($query);
                },
                'required' => false,
            ])
            ->setAction($this->generateUrl('admin_activity_delete', ['id' => $activity->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                $this->repository->deleteActivity($activity, $deleteForm->get('activity')->getData());
                $this->flashSuccess('action.delete.success');
            } catch (Exception $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('admin_activity');
        }

        return $this->render(
            'activity/delete.html.twig',
            [
                'activity' => $activity,
                'stats' => $stats,
                'form' => $deleteForm->createView(),
            ]
        );
    }

    /**
     * @Route(path="/export", name="activity_export", methods={"GET"})
     */
    public function exportAction(Request $request, EntityWithMetaFieldsExporter $exporter)
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
     * @param Activity $activity
     * @param Request $request
     * @return RedirectResponse|Response
     */
    protected function renderActivityForm(Activity $activity, Request $request)
    {
        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $editForm = $this->createEditForm($activity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->repository->saveActivity($activity);
                $this->flashSuccess('action.update.success');

                if ($editForm->has('create_more') && $editForm->get('create_more')->getData() === true) {
                    $newActivity = new Activity();
                    $newActivity->setProject($activity->getProject());
                    $editForm = $this->createEditForm($newActivity);
                    $editForm->get('create_more')->setData(true);
                    $activity = $newActivity;
                } else {
                    return $this->redirectToRoute('admin_activity');
                }
            } catch (Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render(
            'activity/edit.html.twig',
            [
                'activity' => $activity,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @param ActivityQuery $query
     * @return FormInterface
     */
    protected function getToolbarForm(ActivityQuery $query)
    {
        return $this->createForm(ActivityToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_activity', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    /**
     * @param Activity $activity
     * @return FormInterface
     */
    private function createEditForm(Activity $activity)
    {
        $currency = $this->configuration->getCustomerDefaultCurrency();
        $url = $this->generateUrl('admin_activity_create');

        if ($activity->getId() !== null) {
            $url = $this->generateUrl('admin_activity_edit', ['id' => $activity->getId()]);
            if (null !== $activity->getProject()) {
                $currency = $activity->getProject()->getCustomer()->getCurrency();
            }
        }

        return $this->createForm(ActivityEditForm::class, $activity, [
            'action' => $url,
            'method' => 'POST',
            'currency' => $currency,
            'create_more' => true,
            'customer' => true,
            'include_budget' => $this->isGranted('budget', $activity)
        ]);
    }
}
