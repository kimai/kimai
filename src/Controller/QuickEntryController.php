<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Form\QuickEntryForm;
use App\Model\QuickEntryModel;
use App\Model\QuickEntryWeek;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to enter times in weekly form.
 *
 * @Route(path="/quick-entry")
 * @Security("is_granted('view_own_timesheet')")
 */
class QuickEntryController extends AbstractController
{
    private $configuration;
    private $timesheetService;
    private $repository;

    public function __construct(SystemConfiguration $configuration, TimesheetService $timesheetService, TimesheetRepository $repository)
    {
        $this->configuration = $configuration;
        $this->timesheetService = $timesheetService;
        $this->repository = $repository;
    }

    /**
     * @Route(path="/{begin}", name="quick-entry", methods={"GET", "POST"})
     * @Security("is_granted('edit_own_timesheet')")
     */
    public function quickEntry(Request $request, ?string $begin = null)
    {
        $mode = $this->timesheetService->getActiveTrackingMode();

        if (!$mode->canEditDuration() && !$mode->canEditEnd()) {
            $this->flashError('Not allowed');

            return $this->redirectToRoute('homepage');
        }

        $factory = $this->getDateTimeFactory();
        if ($begin === null) {
            $begin = $factory->createDateTime();
        } else {
            $begin = $factory->createDateTime($begin);
        }

        $startWeek = $factory->getStartOfWeek($begin);
        $endWeek = $factory->getEndOfWeek($begin);

        $tmpDay = clone $startWeek;
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
        $query->setUser($this->getUser());

        $result = $this->repository->getTimesheetResult($query);

        $rows = [];
        /** @var Timesheet $timesheet */
        foreach ($result->getResults(true) as $timesheet) {
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

        // attach recent activities
        $timesheets = $this->repository->getRecentActivities($this->getUser(), null, 5);
        foreach ($timesheets as $timesheet) {
            $id = $timesheet->getProject()->getId() . '_' . $timesheet->getActivity()->getId();
            if (\array_key_exists($id, $rows)) {
                continue;
            }
            $rows[$id] = [
                'days' => $week,
                'project' => $timesheet->getProject(),
                'activity' => $timesheet->getActivity()
            ];
        }

        $beginTime = $this->configuration->getTimesheetDefaultBeginTime();
        $user = $this->getUser();

        /** @var QuickEntryModel[] $models */
        $models = [];
        foreach ($rows as $id => $row) {
            $model = new QuickEntryModel($user, $row['project'], $row['activity']);
            foreach ($row['days'] as $dayId => $day) {
                if (!\array_key_exists('entry', $day)) {
                    $tmp = new Timesheet();
                    $tmp->setProject($row['project']);
                    $tmp->setActivity($row['activity']);
                    $tmp->setBegin($day['day']);
                    $tmp->getBegin()->modify($beginTime);
                    $model->addTimesheet($tmp);
                } else {
                    $model->addTimesheet($day['entry']);
                }
            }
            $models[] = $model;
        }

        $empty = null;

        $amountEmptyRows = 3;
        if (\count($models) < 5) {
            $amountEmptyRows = 5;
        }

        for ($a = 0; $a < $amountEmptyRows; $a++) {
            $model = new QuickEntryModel();
            foreach ($week as $dayId => $day) {
                $tmp = new Timesheet();
                $tmp->setBegin($day['day']);
                $tmp->getBegin()->modify($beginTime);
                $model->addTimesheet($tmp);
            }

            if ($empty === null) {
                $empty = $model;
            }

            $models[] = $model;
        }

        $formModel = new QuickEntryWeek($startWeek, $models);

        $form = $this->createForm(QuickEntryForm::class, $formModel, [
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'prototype_data' => $empty,
        ]);

        $form->handleRequest($request);

        $allowDelete = $this->isGranted('delete_own_timesheet');

        if ($form->isSubmitted() && $form->isValid()) {
            $saveTimesheets = [];
            $deleteTimesheets = [];
            /** @var QuickEntryWeek $data */
            $data = $form->getData();
            foreach ($data->getRows() as $tmpModel) {
                foreach ($tmpModel->getTimesheets() as $timesheet) {
                    $save = false;

                    if ($timesheet->getId() !== null) {
                        if ($timesheet->getDuration() === null || $timesheet->getEnd() === null) {
                            if ($allowDelete) {
                                $deleteTimesheets[] = $timesheet;
                            }
                        } else {
                            $save = true;
                        }
                    } else {
                        if ($timesheet->getDuration() !== null) {
                            $save = true;
                        }
                    }

                    if ($save) {
                        $timesheet->setUser($this->getUser());
                        $timesheet->setActivity($tmpModel->getActivity());
                        $timesheet->setProject($tmpModel->getProject());
                        $saveTimesheets[] = $timesheet;
                    }
                }
            }

            $updated = false;
            if (\count($deleteTimesheets) > 0) {
                $this->timesheetService->deleteMultipleTimesheets($deleteTimesheets);
                $updated = true;
            }

            if (\count($saveTimesheets) > 0) {
                $this->timesheetService->updateMultipleTimesheets($saveTimesheets);
                $updated = true;
            }

            if ($updated) {
                return $this->redirectToRoute('quick-entry', ['begin' => $begin->format('Y-m-d')]);
            }
        }

        return $this->render('quick-entry/index.html.twig', [
            'days' => $week,
            'week' => $rows,
            'form' => $form->createView(),
        ]);
    }
}
