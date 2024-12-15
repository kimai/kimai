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
use App\Form\TimesheetPreCreateForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class TimesheetAbstractController extends AbstractController
{
    public function __construct(
        protected TimesheetRepository $repository,
        protected EventDispatcherInterface $dispatcher,
        protected TimesheetService $service,
        protected SystemConfiguration $configuration,
        protected TagRepository $tagRepository
    ) {
    }

    protected function getTrackingMode(): TrackingModeInterface
    {
        return $this->service->getActiveTrackingMode();
    }

    protected function index(TimesheetQuery $query, Request $request, string $route, string $paginationRoute, string $location): Response
    {
        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute($route);
        }

        $canSeeRate = $this->canSeeRate();
        $canSeeUsername = $this->canSeeUsername();

        $this->prepareQuery($query);

        $result = $this->repository->getTimesheetResult($query);
        $metaColumns = $this->findMetaColumns($query, $location);

        $table = new DataTable($this->getTableName(), $query);
        $table->setPagination($result->getPagerfanta());
        $table->setSearchForm($form);
        $table->setBatchForm($this->getMultiUpdateActionForm());
        $table->setPaginationRoute($paginationRoute);
        $table->setReloadEvents('kimai.timesheetUpdate kimai.timesheetDelete');

        $table->addColumn('date', ['class' => 'alwaysVisible text-nowrap', 'orderBy' => 'begin']);

        if ($this->canSeeStartEndTime()) {
            $table->addColumn('starttime', ['class' => 'd-none d-sm-table-cell text-center text-nowrap', 'orderBy' => 'begin']);
            $table->addColumn('endtime', ['class' => 'd-none d-sm-table-cell text-center text-nowrap', 'orderBy' => 'end']);
        }

        if ($this->configuration->isBreakTimeEnabled()) {
            $table->addColumn('break', ['class' => 'text-end text-nowrap']);
        }

        $table->addColumn('duration', ['class' => 'text-end text-nowrap']);

        if ($canSeeRate) {
            $table->addColumn('hourlyRate', ['class' => 'text-end d-none text-nowrap']);
            $table->addColumn('internalRate', ['class' => 'text-end text-nowrap d-none d-xxl-table-cell']);
            $table->addColumn('rate', ['class' => 'text-end text-nowrap']);
        }

        $table->addColumn('customer', ['class' => 'd-none d-md-table-cell']);
        $table->addColumn('project', ['class' => 'd-none d-xl-table-cell']);
        $table->addColumn('activity', ['class' => 'd-none d-xl-table-cell']);
        $table->addColumn('description', ['class' => 'd-none']);
        $table->addColumn('tags', ['class' => 'd-none', 'orderBy' => false]);

        foreach ($metaColumns as $metaColumn) {
            $table->addColumn('mf_' . $metaColumn->getName(), ['title' => $metaColumn->getLabel(), 'class' => 'd-none', 'orderBy' => false, 'data' => $metaColumn]);
        }

        if ($canSeeUsername) {
            $table->addColumn('username', ['class' => 'd-none d-md-table-cell', 'orderBy' => false]);
        }

        $table->addColumn('billable', ['class' => 'text-center d-none w-min', 'orderBy' => false]);
        $table->addColumn('exported', ['class' => 'text-center d-none w-min', 'orderBy' => false]);
        $table->addColumn('actions', ['class' => 'actions']);

        $page = $this->createPageSetup();
        $page->setActionName($this->getActionName());

        return $this->render('timesheet/index.html.twig', [
            'view_rate' => $canSeeRate,
            'page_setup' => $page,
            'dataTable' => $table,
            'action_single' => $this->getActionNameSingle(),
            'stats' => $result->getStatistic(),
            'showSummary' => $this->includeSummary(),
            'metaColumns' => $metaColumns,
            'allowMarkdown' => $this->hasMarkdownSupport(),
            'editRoute' => $this->getEditRoute()
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

    protected function edit(Timesheet $entry, Request $request): Response
    {
        $event = new TimesheetMetaDefinitionEvent($entry);
        $this->dispatcher->dispatch($event);

        $page = $request->get('page');
        $page = is_numeric($page) ? (int) $page : 1;
        $editForm = $this->getEditForm($entry, $page);
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

        return $this->render('timesheet/edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'route_back' => $this->getTimesheetRoute(),
            'timesheet' => $entry,
            'form' => $editForm->createView(),
            'template' => $this->getTrackingMode()->getEditTemplate(),
        ]);
    }

    protected function create(Request $request): Response
    {
        $entry = $this->service->createNewTimesheet($this->getUser(), $request);

        $preForm = $this->createFormForGetRequest(TimesheetPreCreateForm::class, $entry, [
            'include_user' => $this->includeUserInForms('create'),
        ]);
        $preForm->submit($request->query->all(), false);

        $createForm = $this->getCreateForm($entry);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            try {
                $this->service->saveNewTimesheet($entry);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute($this->getTimesheetRoute());
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $createForm);
            }
        }

        return $this->render('timesheet/edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'route_back' => $this->getTimesheetRoute(),
            'timesheet' => $entry,
            'form' => $createForm->createView(),
            'template' => $this->getTrackingMode()->getEditTemplate(),
        ]);
    }

    protected function duplicate(Timesheet $timesheet, Request $request): Response
    {
        $copyTimesheet = clone $timesheet;
        $copyTimesheet->resetRates();

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
                $this->handleFormUpdateException($ex, $form);
            }
        }

        return $this->render('timesheet/edit.html.twig', [
            'timesheet' => $copyTimesheet,
            'form' => $form->createView(),
            'template' => $this->getTrackingMode()->getEditTemplate(),
        ]);
    }

    protected function export(string $type, Request $request, ServiceExport $serviceExport): Response
    {
        $exporter = $serviceExport->getTimesheetExporterById($type);

        if (null === $exporter) {
            throw $this->createNotFoundException();
        }

        $query = $this->createDefaultQuery();
        $query->setOrder(BaseQuery::ORDER_ASC);

        $form = $this->getToolbarForm($query);
        $request->query->set('performSearch', true);

        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute($this->getTimesheetRoute());
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

        return $exporter->render($entries->getResults(), $query);
    }

    protected function multiUpdate(Request $request): Response
    {
        $dto = new TimesheetMultiUpdateDTO();

        // initial request from the listing posts a different form
        $form = $this->getMultiUpdateActionForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data instanceof MultiUpdateTableDTO) {
                $dto->setEntities($data->getEntities());
            }
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
            $this->flashWarning(\sprintf('You are missing the permission to edit %s timesheets', $disallowed));
        }

        $dto->setEntities($timesheets);

        if (\count($timesheets) === 0) {
            return $this->redirectToRoute($this->getTimesheetRoute());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $execute = false;
            /** @var Timesheet $timesheet */
            foreach ($timesheets as $timesheet) {
                if ($dto->isReplaceDescription()) {
                    $timesheet->setDescription($dto->getDescription());
                    $execute = true;
                } elseif($dto->getDescription() !== null && $dto->getDescription() !== '') {
                    $timesheet->setDescription($timesheet->getDescription() . PHP_EOL . $dto->getDescription());
                    $execute = true;
                }
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
                    $this->service->updateMultipleTimesheets($timesheets);
                    $this->flashSuccess('action.update.success');

                    return $this->redirectToRoute($this->getTimesheetRoute());
                } catch (\Exception $ex) {
                    $this->flashUpdateException($ex);
                }
            } else {
                $this->flashSuccess(\sprintf('No changes for %s entries detected.', \count($timesheets)));

                return $this->redirectToRoute($this->getTimesheetRoute());
            }
        }

        return $this->render('timesheet/multi-update.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'form' => $form->createView(),
            'dto' => $dto,
            'back' => $this->getTimesheetRoute(),
        ]);
    }

    protected function multiDelete(Request $request): Response
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

    protected function prepareQuery(TimesheetQuery $query): void
    {
        $query->setUser($this->getUser());
    }

    protected function getMultiUpdateForm(TimesheetMultiUpdateDTO $multiUpdate): FormInterface
    {
        return  $this->createForm(TimesheetMultiUpdate::class, $multiUpdate, [
            'action' => $this->generateUrl($this->getMultiUpdateRoute(), []),
            'method' => 'POST',
            'include_exported' => $this->isGranted($this->getPermissionEditExport()),
            'include_billable' => $this->isGranted($this->getPermissionEditBillable()),
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

    /**
     * @param class-string<FormTypeInterface> $formClass
     */
    protected function generateCreateForm(Timesheet $entry, string $formClass, string $action): FormInterface
    {
        $mode = $this->getTrackingMode();

        return $this->createForm($formClass, $entry, [
            'action' => $action,
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_billable' => $this->isGranted('edit_billable', $entry),
            'include_user' => $this->includeUserInForms('create'),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
            'timezone' => $this->getDateTimeFactory()->getTimezone(),
            'customer' => true,
            'create_activity' => $this->isGranted('create_activity'),
        ]);
    }

    private function getEditForm(Timesheet $entry, int $page): FormInterface
    {
        $mode = $this->getTrackingMode();

        return $this->createForm($this->getEditFormClassName(), $entry, [
            'action' => $this->generateUrl($this->getEditRoute(), [
                'id' => $entry->getId(),
                'page' => $page,
            ]),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_billable' => $this->isGranted('edit_billable', $entry),
            'include_user' => $this->includeUserInForms('edit'),
            'create_activity' => $this->isGranted('create_activity'),
            'allow_begin_datetime' => $mode->canEditBegin(),
            'allow_end_datetime' => $mode->canEditEnd(),
            'allow_duration' => $mode->canEditDuration(),
            'duration_minutes' => $this->configuration->getTimesheetIncrementDuration(),
            'timezone' => $this->getDateTimeFactory()->getTimezone(),
            'customer' => true,
        ]);
    }

    protected function getToolbarForm(TimesheetQuery $query): FormInterface
    {
        return $this->createSearchForm(TimesheetToolbarForm::class, $query, [
            'action' => $this->generateUrl($this->getTimesheetRoute(), [
                'page' => $query->getPage(),
            ]),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'include_user' => $this->includeUserInForms('toolbar'),
        ]);
    }

    protected function getPermissionEditExport(): string
    {
        return 'edit_export_own_timesheet';
    }

    protected function getPermissionEditBillable(): string
    {
        return 'edit_billable_own_timesheet';
    }

    protected function getPermissionEditRate(): string
    {
        return 'edit_rate_own_timesheet';
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    protected function getEditFormClassName(): string
    {
        return TimesheetEditForm::class;
    }

    protected function includeSummary(): bool
    {
        return (bool) $this->getUser()->getPreferenceValue('daily_stats', false, false);
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

    protected function canSeeRate(): bool
    {
        return $this->isGranted('view_rate_own_timesheet');
    }

    protected function canSeeUsername(): bool
    {
        return false;
    }

    protected function hasMarkdownSupport(): bool
    {
        return true;
    }

    protected function getTableName(): string
    {
        return 'timesheet';
    }

    protected function getActionName(): string
    {
        return 'timesheets';
    }

    protected function getActionNameSingle(): string
    {
        return 'timesheet';
    }

    protected function createPageSetup(): PageSetup
    {
        $page = new PageSetup('timesheet.title');
        $page->setHelp('timesheet.html');

        return $page;
    }

    abstract protected function getDuplicateForm(Timesheet $entry, Timesheet $original): FormInterface;

    abstract protected function getCreateForm(Timesheet $entry): FormInterface;
}
