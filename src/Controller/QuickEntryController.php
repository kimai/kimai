<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Event\QuickEntryMetaDisplayEvent;
use App\Form\QuickEntryForm;
use App\Form\WeekByUserForm;
use App\Model\QuickEntryWeek;
use App\Reporting\WeekByUser\WeekByUser;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\FavoriteRecordService;
use App\Timesheet\TimesheetService;
use App\Utils\PageSetup;
use App\WorkingTime\WorkingTimeService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to enter times in weekly form.
 */
#[IsGranted('quick-entry')]
final class QuickEntryController extends AbstractController
{
    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly TimesheetService $timesheetService,
        private readonly TimesheetRepository $repository,
        private readonly FavoriteRecordService $favoriteRecordService,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly WorkingTimeService $workingTimeService,
    )
    {
    }

    #[Route(path: '/quick_entry/', name: 'quick_entry', methods: ['GET', 'POST'])]
    public function quickEntry(Request $request): Response
    {
        $user = $this->getUser();
        $factory = $this->getDateTimeFactory($user);
        $defaultDate = $factory->createDateTime();

        $values = new WeekByUser();
        $values->setUser($user);
        $values->setDate($defaultDate);

        $weeklyForm = $this->createFormForGetRequest(WeekByUserForm::class, $values, [
            'include_user' => $this->isGranted('view_other_timesheet'),
            'timezone' => $factory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'attr' => ['name' => 'quick_entry_weekrange_form']
        ]);

        $weeklyForm->submit($request->query->all(), false);

        $user = $values->getUser() ?? $user;
        $factory = $this->getDateTimeFactory($user);

        $begin = $values->getDate();

        if ($begin === null) {
            $begin = $factory->createDateTime();
        }

        $startWeek = $factory->getStartOfWeek($begin);
        $endWeek = $factory->getEndOfWeek($begin);

        $tmpDay = clone $startWeek;
        /** @var array<string, array{day: \DateTime}> $week */
        $week = [];
        while ($tmpDay < $endWeek) {
            $nextDay = clone $tmpDay;
            $week[$nextDay->format('Y-m-d')] = ['day' => $nextDay];
            $tmpDay = $tmpDay->modify('+1 day');
        }

        $query = new TimesheetQuery();
        $query->setBegin($startWeek);
        $query->setEnd($endWeek);
        $query->setName('quickEntryForm');
        $query->setUser($user);

        $result = $this->repository->getTimesheetResult($query);

        $rows = [];
        foreach ($result->getResults() as $timesheet) {
            $i = 0;
            $id = $timesheet->getProject()->getId() . '_' . $timesheet->getActivity()->getId();
            $day = $timesheet->getBegin()->format('Y-m-d');

            while (\array_key_exists($id, $rows) && \array_key_exists('entry', $rows[$id]['days'][$day])) {
                $i++;
                $id = $timesheet->getProject()->getId() . '_' . $timesheet->getActivity()->getId() . '_' . $i;
            }

            if (!\array_key_exists($id, $rows)) {
                $rows[$id] = [
                    'days' => $week,
                    'project' => $timesheet->getProject(),
                    'activity' => $timesheet->getActivity()
                ];
            }

            $rows[$id]['days'][$day]['entry'] = $timesheet;
        }

        ksort($rows);

        // this should also check via lock service
        $locked = $this->workingTimeService->isApproved($user, $endWeek);

        if (!$locked) {
            // attach recent activities
            $amount = $this->configuration->getQuickEntriesRecentAmount();
            if ($amount > 0) {
                $takeOverWeeks = $this->configuration->find('quick_entry.recent_activity_weeks');
                $startFrom = null;
                if (is_numeric($takeOverWeeks) && \intval($takeOverWeeks) > 0) {
                    $startFrom = clone $startWeek;
                    $startFrom->modify(\sprintf('-%s weeks', (string) $takeOverWeeks));
                }

                $favorites = $this->favoriteRecordService->favoriteEntries($user, $amount);
                foreach ($favorites as $favorite) {
                    $timesheet = $favorite->getTimesheet();
                    if ($startFrom !== null && !$favorite->isFavorite() && $startFrom > $timesheet->getBegin()) {
                        continue;
                    }

                    $id = $timesheet->getProject()->getId() . '_' . $timesheet->getActivity()->getId();
                    if (\array_key_exists($id, $rows)) {
                        continue;
                    }
                    // edge case: a project that starts and ends between the start and end date allows to select it from the dropdown,
                    // but it is better to hide a row than displaying already ended projects
                    if ($timesheet->getProject() !== null && (!$timesheet->getProject()->isVisibleAtDate($startWeek) && !$timesheet->getProject()->isVisibleAtDate($endWeek))) {
                        continue;
                    }
                    // make sure no invisible entries are included
                    if (!$this->isGranted('start', $timesheet)) {
                        continue;
                    }
                    $rows[$id] = [
                        'days' => $week,
                        'project' => $timesheet->getProject(),
                        'activity' => $timesheet->getActivity()
                    ];
                }
            }
        }

        $defaultBegin = $factory->createDateTime($this->configuration->getTimesheetDefaultBeginTime());
        $defaultHour = (int) $defaultBegin->format('H');
        $defaultMinute = (int) $defaultBegin->format('i');

        // find additional meta-fields exclusively for QuickEntry view
        $event = new QuickEntryMetaDisplayEvent($query);
        $this->dispatcher->dispatch($event);
        $metaFields = $event->getFields();

        $formModel = new QuickEntryWeek($startWeek);

        foreach ($rows as $id => $row) {
            $model = $formModel->addRow($user, $row['project'], $row['activity']);
            foreach ($row['days'] as $dayId => $day) {
                if (!\array_key_exists('entry', $day)) {
                    // fill all rows and columns to make sure we do not have missing records
                    $tmp = $this->timesheetService->createNewTimesheet($user);
                    $tmp->setDuration(null);
                    $tmp->setProject($row['project']);
                    $tmp->setActivity($row['activity']);
                    $newTime = \DateTime::createFromInterface($day['day']);
                    $newTime = $newTime->setTime($defaultHour, $defaultMinute);
                    $tmp->setBegin($newTime);
                    $this->timesheetService->prepareNewTimesheet($tmp);
                    $model->addTimesheet($tmp);
                } else {
                    $model->addTimesheet($day['entry']);
                }
            }
            $model->setMetaFields($metaFields);
        }

        // create prototype model
        $empty = $formModel->createRow($user);
        $empty->setMetaFields($metaFields);
        $empty->markAsPrototype();
        foreach ($week as $dayId => $day) {
            $tmp = $this->timesheetService->createNewTimesheet($user);
            $tmp->setDuration(null);
            $newTime = \DateTime::createFromInterface($day['day']);
            $newTime = $newTime->setTime($defaultHour, $defaultMinute, 0, 0);
            $tmp->setBegin($newTime);
            $this->timesheetService->prepareNewTimesheet($tmp);
            $empty->addTimesheet($tmp);
        }

        // add empty rows for simpler starting
        $minRows = \intval($this->configuration->find('quick_entry.minimum_rows'));
        if (!$locked && $formModel->countRows() < $minRows) {
            $newRows = $minRows - $formModel->countRows();
            for ($a = 0; $a < $newRows; $a++) {
                $model = $formModel->addRow($user);
                foreach ($week as $dayId => $day) {
                    $tmp = $this->timesheetService->createNewTimesheet($user);
                    $tmp->setDuration(null);
                    $newTime = \DateTime::createFromInterface($day['day']);
                    $newTime = $newTime->setTime($defaultHour, $defaultMinute, 0, 0);
                    $tmp->setBegin($newTime);
                    $this->timesheetService->prepareNewTimesheet($tmp);
                    $model->addTimesheet($tmp);
                }
                $model->setMetaFields($metaFields);
            }
        }

        $form = $this->createForm(QuickEntryForm::class, $formModel, [
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'prototype_data' => $empty,
            'start_date' => $startWeek,
            'end_date' => $endWeek,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var QuickEntryWeek $data */
            $data = $form->getData();

            $saveTimesheets = [];
            $deleteTimesheets = [];

            foreach ($data->getRows() as $tmpModel) {
                foreach ($tmpModel->getTimesheets() as $timesheet) {
                    if ($timesheet->getId() !== null) {
                        $duration = $timesheet->getDuration(false);
                        // running timesheets also have a empty duration.
                        // we distinguish them from temporary ones, to make sure they will not be deleted
                        if ($timesheet->isRunning()) {
                            $saveTimesheets[] = $timesheet;
                        } elseif ($duration === null) {
                            if ($this->isGranted('delete', $timesheet)) {
                                $deleteTimesheets[] = $timesheet;
                            }
                        } else {
                            $saveTimesheets[] = $timesheet;
                        }
                    } else {
                        if ($timesheet->getDuration() !== null) {
                            $saveTimesheets[] = $timesheet;
                        }
                    }
                }
            }

            try {
                $saved = false;
                if (\count($deleteTimesheets) > 0) {
                    $this->timesheetService->deleteMultipleTimesheets($deleteTimesheets);
                    $saved = true;
                }

                if (\count($saveTimesheets) > 0) {
                    $saveMe = [];
                    foreach ($saveTimesheets as $timesheet) {
                        if ($timesheet->getId() === null || $this->isGranted('edit', $timesheet)) {
                            $saveMe[] = $timesheet;
                        }
                    }
                    if (\count($saveMe) > 0) {
                        $this->timesheetService->updateMultipleTimesheets($saveMe);
                        $saved = true;
                    }
                }

                if ($saved) {
                    $this->flashSuccess('action.update.success');

                    return $this->redirectToRoute('quick_entry', ['date' => $begin->format('Y-m-d'), 'user' => $user->getId()]);
                }
            } catch (\Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        $page = new PageSetup('quick_entry.title');
        $page->setHelp('weekly-times.html');
        $page->setPaginationForm($weeklyForm);
        $page->setActionName('weekly-times');

        return $this->render('quick-entry/index.html.twig', [
            'page_setup' => $page,
            'days' => $week,
            'form' => $form->createView(),
            'metaColumns' => $metaFields,
            'locked' => $locked,
        ]);
    }
}
