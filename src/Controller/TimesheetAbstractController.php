<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\TimesheetConfiguration;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Event\TimesheetMetaDisplayEvent;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * @var TrackingModeService
     */
    protected $trackingModeService;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(
        UserDateTimeFactory $dateTime,
        TimesheetConfiguration $configuration,
        TimesheetRepository $repository,
        TrackingModeService $service,
        EventDispatcherInterface $dispatcher
    ) {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->trackingModeService = $service;
        $this->dispatcher = $dispatcher;
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

    protected function index($page, Request $request, string $renderTemplate, string $location): Response
    {
        $query = new TimesheetQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        if (null !== $query->getBegin()) {
            $query->getBegin()->setTime(0, 0, 0);
        }
        if (null !== $query->getEnd()) {
            $query->getEnd()->setTime(23, 59, 59);
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

        $this->prepareQuery($query);

        $pager = $this->getRepository()->getPagerfantaForQuery($query);

        return $this->render($renderTemplate, [
            'entries' => $pager,
            'page' => $query->getPage(),
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'showSummary' => $this->includeSummary(),
            'showStartEndTime' => $this->canSeeStartEndTime(),
            'metaColumns' => $this->findMetaColumns($query, $location),
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @param string $location
     * @return MetaTableTypeInterface[]
     */
    protected function findMetaColumns(TimesheetQuery $query, string $location): array
    {
        $event = new TimesheetMetaDisplayEvent($query, $location);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    protected function edit(Timesheet $entry, Request $request, string $renderTemplate): Response
    {
        $event = new TimesheetMetaDefinitionEvent($entry);
        $this->dispatcher->dispatch($event);

        $editForm = $this->getEditForm($entry, $request->get('page'));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->getRepository()->save($entry);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute(), ['page' => $request->get('page', 1)]);
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $editForm->createView(),
        ]);
    }

    protected function create(Request $request, string $renderTemplate, ProjectRepository $projectRepository, ActivityRepository $activityRepository, TagRepository $tagRepository): Response
    {
        $entry = new Timesheet();
        $entry->setUser($this->getUser());

        if ($request->query->get('project')) {
            $project = $projectRepository->find($request->query->get('project'));
            $entry->setProject($project);
        }

        if ($request->query->get('activity')) {
            $activity = $activityRepository->find($request->query->get('activity'));
            $entry->setActivity($activity);
        }

        if ($request->query->get('tags')) {
            $tagNames = explode(',', $request->query->get('tags'));
            foreach ($tagNames as $tagName) {
                $tag = $tagRepository->findTagByName($tagName);
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                }
                $entry->addTag($tag);
            }
        }

        $event = new TimesheetMetaDefinitionEvent($entry);
        $this->dispatcher->dispatch($event);

        $mode = $this->getTrackingMode();
        $mode->create($entry, $request);

        $createForm = $this->getCreateForm($entry, $mode);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            try {
                if (null === $entry->getEnd()) {
                    $this->getRepository()->stopActiveEntries(
                        $entry->getUser(),
                        $this->configuration->getActiveEntriesHardLimit()
                    );
                }
                $this->getRepository()->save($entry);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute());
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $createForm->createView(),
        ]);
    }

    protected function export(Request $request, string $renderTemplate, string $location): Response
    {
        $query = new TimesheetQuery();

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

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

        $this->prepareQuery($query);

        $entries = $this->getRepository()->getTimesheetsForQuery($query);

        return $this->render($renderTemplate, [
            'entries' => $entries,
            'query' => $query,
            'metaColumns' => $this->findMetaColumns($query, $location),
        ]);
    }

    protected function prepareQuery(TimesheetQuery $query)
    {
        $query->setUser($this->getUser());
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
