<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Timesheet\TrackingModeService;
use App\Timesheet\UserDateTimeFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class TimesheetAbstractController extends AbstractController
{
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;
    /**
     * @var TimesheetRepository
     */
    protected $repository;
    /**
     * @var TrackingModeInterface
     */
    protected $trackingModeService;

    public function __construct(
        UserDateTimeFactory $dateTime,
        TimesheetConfiguration $configuration,
        TimesheetRepository $repository,
        TrackingModeService $service
    ) {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->trackingModeService = $service;
    }

    protected function getTrackingMode(): TrackingModeInterface
    {
        return $this->trackingModeService->getActiveMode();
    }

    protected function getSoftLimit(): int
    {
        return $this->configuration->getActiveEntriesSoftLimit();
    }

    protected function getRepository(): TimesheetRepository
    {
        return $this->repository;
    }

    protected function index($page, Request $request, string $renderTemplate)
    {
        $query = new TimesheetQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
            if (null !== $query->getBegin()) {
                $query->getBegin()->setTime(0, 0, 0);
            }
            if (null !== $query->getEnd()) {
                $query->getEnd()->setTime(23, 59, 59);
            }
        }

        if (!$this->includeUserInForms()) {
            $query->setUser($this->getUser());
        }

        $tags = $query->getTags(true);
        if (!empty($tags)) {
            /** @var TagRepository $tagRepo */
            $tagRepo = $this->getDoctrine()->getRepository(Tag::class);
            $query->setTags(
                new ArrayCollection(
                    $tagRepo->findIdsByTagNameList(implode(',', $tags))
                )
            );
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render($renderTemplate, [
            'entries' => $entries,
            'page' => $query->getPage(),
            'query' => $query,
            'showFilter' => $form->isSubmitted(),
            'toolbarForm' => $form->createView(),
            'showSummary' => $this->includeSummary(),
            'showStartEndTime' => $this->canSeeStartEndTime()
        ]);
    }

    /**
     * @param Timesheet $entry
     * @param Request $request
     * @param string $renderTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function edit(Timesheet $entry, Request $request, string $renderTemplate)
    {
        $editForm = $this->getEditForm($entry, $request->get('page'));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entry);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute($this->getTimesheetRoute(), ['page' => $request->get('page', 1)]);
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $editForm->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param string $renderTemplate
     * @param ProjectRepository $projectRepository
     * @param ActivityRepository $activityRepository
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function create(Request $request, string $renderTemplate, ProjectRepository $projectRepository, ActivityRepository $activityRepository)
    {
        $entry = new Timesheet();
        $entry->setUser($this->getUser());
        $entry->setBegin($this->dateTime->createDateTime());

        if ($request->query->get('project')) {
            $project = $projectRepository->find($request->query->get('project'));
            $entry->setProject($project);
        }

        if ($request->query->get('activity')) {
            $activity = $activityRepository->find($request->query->get('activity'));
            $entry->setActivity($activity);
        }

        $mode = $this->getTrackingMode();
        $mode->create($entry, $request);

        $createForm = $this->getCreateForm($entry, $mode);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            try {
                if (null === $entry->getEnd()) {
                    $this->getRepository()->stopActiveEntries(
                        $entry->getUser(),
                        $this->configuration->getActiveEntriesHardLimit()
                    );
                }
                $entityManager->persist($entry);
                $entityManager->flush();

                $this->flashSuccess('action.update.success');
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute($this->getTimesheetRoute());
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $createForm->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param string $renderTemplate
     * @return Response
     */
    protected function export(Request $request, string $renderTemplate)
    {
        $query = new TimesheetQuery();
        $query->setResultType(TimesheetQuery::RESULT_TYPE_OBJECTS);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
        }

        // by default the current month is exported, but it can be overwritten
        // this should not be removed, otherwise we would export EVERY available record in the admin section
        // as the default toolbar query does neither limit the user nor the date-range!
        if (null === $query->getBegin()) {
            $query->setBegin($this->dateTime->createDateTime('first day of this month'));
        }
        $query->getBegin()->setTime(0, 0, 0);

        if (null === $query->getEnd()) {
            $query->setEnd($this->dateTime->createDateTime('last day of this month'));
        }
        $query->getEnd()->setTime(23, 59, 59);

        if (!$this->includeUserInForms()) {
            $query->setUser($this->getUser());
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render($renderTemplate, [
            'entries' => $entries,
            'query' => $query,
        ]);
    }

    protected function getCreateForm(Timesheet $entry, TrackingModeInterface $mode): FormInterface
    {
        return $this->createForm($this->getCreateFormClassName(), $entry, [
            'action' => $this->generateUrl($this->getCreateRoute()),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_user' => $this->includeUserInForms(),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'customer' => true,
        ]);
    }

    /**
     * @param Timesheet $entry
     * @param int $page
     * @return FormInterface
     */
    protected function getEditForm(Timesheet $entry, $page)
    {
        $mode = $this->getTrackingMode();

        return $this->createForm($this->getEditFormClassName(), $entry, [
            'action' => $this->generateUrl($this->getEditRoute(), [
                'id' => $entry->getId(),
                'page' => $page,
            ]),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_user' => $this->includeUserInForms(),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'customer' => true,
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @return FormInterface
     */
    protected function getToolbarForm(TimesheetQuery $query)
    {
        return $this->createForm(TimesheetToolbarForm::class, $query, [
            'action' => $this->generateUrl($this->getTimesheetRoute(), [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
            'include_user' => $this->includeUserInForms(),
        ]);
    }

    protected function getCreateFormClassName(): string
    {
        return TimesheetEditForm::class;
    }

    protected function getEditFormClassName(): string
    {
        return TimesheetEditForm::class;
    }

    protected function includeSummary(): bool
    {
        return (bool) $this->getUser()->getPreferenceValue('timesheet.daily_stats', false);
    }

    protected function includeUserInForms(): bool
    {
        return false;
    }

    protected function getTimesheetRoute(): string
    {
        return 'timesheet';
    }

    protected function getEditRoute(): string
    {
        return 'timesheet_edit';
    }

    protected function getCreateRoute(): string
    {
        return 'timesheet_create';
    }

    protected function canSeeStartEndTime(): bool
    {
        return $this->getTrackingMode()->canSeeBeginAndEndTimes();
    }
}
