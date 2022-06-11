<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Project\ProjectStatisticService;
use App\Reporting\ProjectView\ProjectViewForm;
use App\Reporting\ProjectView\ProjectViewQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ProjectViewController extends AbstractController
{
    /**
     * @Route(path="/reporting/project_view", name="report_project_view", methods={"GET","POST"})
     * @Security("is_granted('view_reporting') and is_granted('budget_any', 'project')")
     */
    public function __invoke(Request $request, ProjectStatisticService $service)
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ProjectViewQuery($dateFactory->createDateTime(), $user);
        $form = $this->createForm(ProjectViewForm::class, $query);
        $form->submit($request->query->all(), false);

        $projects = $service->findProjectsForView($query);
        $entries = $service->getProjectView($user, $projects, $query->getToday());

        $byCustomer = [];
        foreach ($entries as $entry) {
            $customer = $entry->getProject()->getCustomer();
            if (!isset($byCustomer[$customer->getId()])) {
                $byCustomer[$customer->getId()] = ['customer' => $customer, 'projects' => []];
            }
            $byCustomer[$customer->getId()]['projects'][] = $entry;
        }

        return $this->render('reporting/project_view.html.twig', [
            'entries' => $byCustomer,
            'form' => $form->createView(),
            'title' => 'report_project_view',
            'tableName' => 'project_view_reporting',
            'now' => $dateFactory->createDateTime(),
        ]);
    }
}
