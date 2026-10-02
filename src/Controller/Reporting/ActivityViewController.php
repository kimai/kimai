<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Activity\ActivityStatisticService;
use App\Controller\AbstractController;
use App\Reporting\ActivityView\ActivityViewForm;
use App\Reporting\ActivityView\ActivityViewQuery;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ActivityViewController extends AbstractController
{
    #[Route(path: '/activity/project_view', name: 'report_activity_view', methods: ['GET', 'POST'])]
    #[IsGranted('report:project')]
    #[IsGranted(new Expression("is_granted('budget_any', 'activity')"))]
    public function __invoke(Request $request, ActivityStatisticService $service): Response
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ActivityViewQuery($dateFactory->createDateTime(), $user);
        $form = $this->createFormForGetRequest(ActivityViewForm::class, $query);
        $form->submit($request->query->all(), false);

        $activities = $service->findActivitiesForView($query);
        $entries = $service->getActivityView($user, $activities, $query->getToday());

        $byCustomer = [];
        foreach ($entries as $entry) {
            $project = $entry->getActivity()->getProject();
            $key = ($project === null || $project->getId() === null ? '__EMPTY__' : $project->getId());
            if (!\array_key_exists($key, $byCustomer)) {
                $byCustomer[$key] = ['project' => $project, 'activities' => [], 'name' => $project !== null ? $project->getName() : ''];
            }
            $byCustomer[$key]['activities'][] = $entry;
        }

        return $this->render('reporting/activity_view.html.twig', [
            'entries' => $byCustomer,
            'form' => $form->createView(),
            'report_title' => 'report_activity_view',
            'tableName' => 'activity_view_reporting',
            'now' => $dateFactory->createDateTime(),
        ]);
    }
}
