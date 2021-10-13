<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\MetaTableTypeInterface;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Event\TimesheetDuplicatePostEvent;
use App\Event\TimesheetDuplicatePreEvent;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\ServiceExport;
use App\Form\MultiUpdate\MultiUpdateTable;
use App\Form\MultiUpdate\MultiUpdateTableDTO;
use App\Form\MultiUpdate\TimesheetMultiUpdate;
use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetExportToolbarForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class TimesheetAbstractController extends AbstractController
{
    /**
     * @var TimesheetRepository
     */
    protected $repository;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var TimesheetService
     */
    protected $service;
    /**
     * @var SystemConfiguration
     */
    protected $configuration;

    public function __construct(
        TimesheetRepository $repository,
        EventDispatcherInterface $dispatcher,
        TimesheetService $timesheetService,
        SystemConfiguration $configuration
    ) {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->service = $timesheetService;
        $this->configuration = $configuration;
    }

    protected function getTrackingMode(): TrackingModeInterface
    {
        return $this->service->getActiveTrackingMode();
    }

    protected function index(TimesheetQuery $query, Request $request, string $route, string $renderTemplate, string $location): Response
    {
        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute($route);
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

        $pager = $this->repository->getPagerfantaForQuery($query);

        return $this->render($renderTemplate, [
            'entries' => $pager,
            'page' => $query->getPage(),
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'multiUpdateForm' => $this->getMultiUpdateActionForm()->createView(),
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
                $this->service->updateTimesheet($entry);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute(), ['page' => $request->get('page', 1)]);
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $editForm->createView(),
        ]);
    }

    protected function getTags(TagRepository $tagRepository, $tagNames)
    {
        $tags = [];
        if (!\is_array($tagNames)) {
            $tagNames = explode(',', $tagNames);
        }
        foreach ($tagNames as $tagName) {
            $tag = $tagRepository->findTagByName($tagName);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
            }
            $tags[] = $tag;
        }

        return $tags;
    }

    protected function create(Request $request, string $renderTemplate, ProjectRepository $projectRepository, ActivityRepository $activityRepository, TagRepository $tagRepository): Response
    {
        $entry = $this->service->createNewTimesheet($this->getUser());

        if ($request->query->get('project')) {
            $project = $projectRepository->find($request->query->get('project'));
            $entry->setProject($project);
        }

        if ($request->query->get('activity')) {
            $activity = $activityRepository->find($request->query->get('activity'));
            $entry->setActivity($activity);
        }

        if ($request->query->get('description')) {
            $description = $request->query->get('description');
            $entry->setDescription($description);
        }

        if ($request->query->get('tags')) {
            foreach ($this->getTags($tagRepository, $request->query->get('tags')) as $tag) {
                $entry->addTag($tag);
            }
        }

        $this->service->prepareNewTimesheet($entry, $request);
        $createForm = $this->getCreateForm($entry);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            try {
                $this->service->saveNewTimesheet($entry);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute());
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render($renderTemplate, [
            'timesheet' => $entry,
            'form' => $createForm->createView(),
        ]);
    }

    protected function duplicate(Timesheet $timesheet, Request $request, string $renderTemplate): Response
    {
        $copyTimesheet = clone $timesheet;

        $event = new TimesheetMetaDefinitionEvent($copyTimesheet);
        $this->dispatcher->dispatch($event);

        $form = $this->getDuplicateForm($copyTimesheet, $timesheet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->dispatcher->dispatch(new TimesheetDuplicatePreEvent($copyTimesheet, $timesheet));
                $this->service->saveNewTimesheet($copyTimesheet);
                $this->dispatcher->dispatch(new TimesheetDuplicatePostEvent($copyTimesheet, $timesheet));
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute());
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render($renderTemplate, [
            'timesheet' => $copyTimesheet,
            'form' => $form->createView(),
        ]);
    }

    protected function export(Request $request, ServiceExport $serviceExport): Response
    {
        $query = $this->createDefaultQuery();

        $form = $this->getExportForm($query);

        if ($request->isMethod(Request::METHOD_POST)) {
            $this->ignorePersistedSearch($request);
        }

        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute($this->getExportRoute());
        }

        $this->prepareQuery($query);

        // make sure that we use the "expected time range"
        if (null !== $query->getBegin()) {
            $query->getBegin()->setTime(0, 0, 0);
        }
        if (null !== $query->getEnd()) {
            $query->getEnd()->setTime(23, 59, 59);
        }

        $entries = $this->repository->getTimesheetResult($query);
        $stats = $entries->getStatistic();

        // perform the real export
        if ($request->isMethod(Request::METHOD_POST)) {
            $type = $request->request->get('exporter');
            if (null !== $type) {
                $exporter = $serviceExport->getTimesheetExporterById($type);

                if (null === $exporter) {
                    $form->addError(new FormError('Invalid timesheet exporter given'));
                } else {
                    return $exporter->render($entries->getResults(true), $query);
                }
            }
        }

        return $this->render('timesheet/layout-export.html.twig', [
            'form' => $form->createView(),
            'route_back' => $this->getTimesheetRoute(),
            'exporter' => $serviceExport->getTimesheetExporter(),
            'stats' => $stats,
        ]);
    }

    protected function multiUpdate(Request $request, string $renderTemplate)
    {
        $dto = new TimesheetMultiUpdateDTO();

        // initial request from the listing posts a different form
        $form = $this->getMultiUpdateActionForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dto->setEntities($form->getData()->getEntities());
        }

        // using a new timesheet to make sure we ONLY use meta-fields which are registered via events
        $fake = new Timesheet();
        $event = new TimesheetMetaDefinitionEvent($fake);
        $this->dispatcher->dispatch($event);

        foreach ($fake->getMetaFields() as $field) {
            $dto->setMetaField(clone $field);
        }

        $form = $this->getMultiUpdateForm($dto);
        $form->handleRequest($request);

        // remove all, which are not allowed to be edited
        $timesheets = [];
        $disallowed = 0;
        /** @var Timesheet $timesheet */
        foreach ($dto->getEntities() as $timesheet) {
            if (!$this->isGranted('edit', $timesheet)) {
                $disallowed++;
                continue;
            }
            $timesheets[] = $timesheet;
        }

        if ($disallowed > 0) {
            $this->flashWarning(sprintf('You are missing the permission to edit %s timesheets', $disallowed));
        }

        $dto->setEntities($timesheets);

        if (\count($dto->getEntities()) === 0) {
            return $this->redirectToRoute($this->getTimesheetRoute());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Timesheet $timesheet */
            $execute = false;
            foreach ($dto->getEntities() as $timesheet) {
                if ($dto->isReplaceTags()) {
                    foreach ($timesheet->getTags() as $tag) {
                        $timesheet->removeTag($tag);
                    }
                    $execute = true;
                }
                foreach ($dto->getTags() as $tag) {
                    $timesheet->addTag($tag);
                    $execute = true;
                }
                if (null !== $dto->getActivity()) {
                    $timesheet->setActivity($dto->getActivity());
                    $execute = true;
                }
                if (null !== $dto->getProject()) {
                    $timesheet->setProject($dto->getProject());
                    $execute = true;
                }
                if (null !== $dto->getUser()) {
                    $timesheet->setUser($dto->getUser());
                    $execute = true;
                }
                if (null !== $dto->isExported()) {
                    $timesheet->setExported($dto->isExported());
                    $execute = true;
                }
                if (null !== $dto->isBillable()) {
                    $timesheet->setBillable($dto->isBillable());
                    $execute = true;
                }

                if ($dto->isRecalculateRates()) {
                    $timesheet->setFixedRate(null);
                    $timesheet->setHourlyRate(null);
                    $timesheet->setInternalRate(null);
                    $execute = true;
                } elseif (null !== $dto->getFixedRate()) {
                    $timesheet->setFixedRate($dto->getFixedRate());
                    $timesheet->setHourlyRate(null);
                    $timesheet->setInternalRate(null);
                    $execute = true;
                } elseif (null !== $dto->getHourlyRate()) {
                    $timesheet->setFixedRate(null);
                    $timesheet->setInternalRate(null);
                    $timesheet->setHourlyRate($dto->getHourlyRate());
                    $execute = true;
                }

                foreach ($dto->getUpdateMeta() as $metaName) {
                    if (null !== ($metaField = $dto->getMetaField($metaName))) {
                        if (null !== ($timesheetMeta = $timesheet->getMetaField($metaName))) {
                            $timesheetMeta->setValue($metaField->getValue());
                        } else {
                            $timesheet->setMetaField(clone $metaField);
                        }
                        $execute = true;
                    }
                }
            }

            if ($execute) {
                try {
                    $this->service->updateMultipleTimesheets($dto->getEntities());
                    $this->flashSuccess('action.update.success');

                    return $this->redirectToRoute($this->getTimesheetRoute());
                } catch (\Exception $ex) {
                    $this->flashUpdateException($ex);
                }
            }
        }

        return $this->render($renderTemplate, [
            'form' => $form->createView(),
            'dto' => $dto,
        ]);
    }

    protected function multiDelete(Request $request)
    {
        $form = $this->getMultiUpdateActionForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();
            $timesheets = [];
            /** @var Timesheet $timesheet */
            foreach ($dto->getEntities() as $timesheet) {
                if (!$this->isGranted('delete', $timesheet)) {
                    continue;
                }
                $timesheets[] = $timesheet;
            }
            $dto->setEntities($timesheets);

            try {
                $this->service->deleteMultipleTimesheets($dto->getEntities());
                $this->flashSuccess('action.delete.success');
            } catch (\Exception $ex) {
                $this->flashDeleteException($ex);
            }
        }

        return $this->redirectToRoute($this->getTimesheetRoute());
    }

    protected function prepareQuery(TimesheetQuery $query)
    {
        $query->setUser($this->getUser());
    }

    protected function getMultiUpdateForm(TimesheetMultiUpdateDTO $multiUpdate): FormInterface
    {
        return  $this->createForm(TimesheetMultiUpdate::class, $multiUpdate, [
            'action' => $this->generateUrl($this->getMultiUpdateRoute(), []),
            'method' => 'POST',
            'include_exported' => $this->isGranted($this->getPermissionEditExport()),
            'include_rate' => $this->isGranted($this->getPermissionEditRate()),
            'include_user' => $this->includeUserInForms('multi'),
        ]);
    }

    protected function getMultiUpdateActionForm(): FormInterface
    {
        $dto = new MultiUpdateTableDTO();

        $dto->addUpdate($this->generateUrl($this->getMultiUpdateRoute()));
        $dto->addDelete($this->generateUrl($this->getMultiDeleteRoute()));

        return $this->createForm(MultiUpdateTable::class, $dto, [
            'action' => $this->generateUrl($this->getTimesheetRoute()),
            'repository' => $this->repository,
            'method' => 'POST',
        ]);
    }

    protected function generateCreateForm(Timesheet $entry, string $formClass, string $action): FormInterface
    {
        $mode = $this->getTrackingMode();

        return $this->createForm($formClass, $entry, [
            'action' => $action,
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_user' => $this->includeUserInForms('create'),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
            'begin_minutes' => $this->configuration->getTimesheetIncrementBegin(),
            'end_minutes' => $this->configuration->getTimesheetIncrementEnd(),
            'timezone' => $this->getDateTimeFactory()->getTimezone(),
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
            'include_user' => $this->includeUserInForms('edit'),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
            'begin_minutes' => $this->configuration->getTimesheetIncrementBegin(),
            'end_minutes' => $this->configuration->getTimesheetIncrementEnd(),
            'timezone' => $this->getDateTimeFactory()->getTimezone(),
            'customer' => true,
        ]);
    }

    protected function getToolbarForm(TimesheetQuery $query): FormInterface
    {
        return $this->createForm(TimesheetToolbarForm::class, $query, [
            'action' => $this->generateUrl($this->getTimesheetRoute(), [
                'page' => $query->getPage(),
            ]),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'method' => 'GET',
            'include_user' => $this->includeUserInForms('toolbar'),
        ]);
    }

    protected function getExportForm(TimesheetQuery $query): FormInterface
    {
        return $this->createForm(TimesheetExportToolbarForm::class, $query, [
            'action' => $this->generateUrl($this->getExportRoute()),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'method' => Request::METHOD_POST,
            'include_user' => $this->includeUserInForms('toolbar'),
        ]);
    }

    protected function getPermissionEditExport(): string
    {
        return 'edit_export_own_timesheet';
    }

    protected function getPermissionEditRate(): string
    {
        return 'edit_rate_own_timesheet';
    }

    protected function getEditFormClassName(): string
    {
        return TimesheetEditForm::class;
    }

    protected function includeSummary(): bool
    {
        return (bool) $this->getUser()->getPreferenceValue('timesheet.daily_stats', false);
    }

    protected function includeUserInForms(string $formName): bool
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

    protected function getMultiUpdateRoute(): string
    {
        return 'timesheet_multi_update';
    }

    protected function getMultiDeleteRoute(): string
    {
        return 'timesheet_multi_delete';
    }

    protected function getExportRoute(): string
    {
        return 'timesheet_export';
    }

    protected function canSeeStartEndTime(): bool
    {
        return $this->getTrackingMode()->canSeeBeginAndEndTimes();
    }

    protected function getQueryNamePrefix(): string
    {
        return 'MyTimes';
    }

    protected function createDefaultQuery(string $suffix = 'Listing'): TimesheetQuery
    {
        $query = new TimesheetQuery();
        $query->setName($this->getQueryNamePrefix() . $suffix);

        return $query;
    }

    abstract protected function getDuplicateForm(Timesheet $entry, Timesheet $original): FormInterface;

    abstract protected function getCreateForm(Timesheet $entry): FormInterface;
}
