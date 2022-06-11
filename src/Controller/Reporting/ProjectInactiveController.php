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
use App\Reporting\ProjectInactive\ProjectInactiveForm;
use App\Reporting\ProjectInactive\ProjectInactiveQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ProjectInactiveController extends AbstractController
{
    /**
     * @Route(path="/reporting/project_inactive", name="report_project_inactive", methods={"GET","POST"})
     * @Security("is_granted('view_reporting') and is_granted('budget_any', 'project')")
     */
    public function __invoke(Request $request, ProjectStatisticService $service)
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();
        $now = $dateFactory->createDateTime();

        $query = new ProjectInactiveQuery($dateFactory->createDateTime('-1 year'), $user);
        $form = $this->createForm(ProjectInactiveForm::class, $query, [
            'timezone' => $user->getTimezone()
        ]);
        $form->submit($request->query->all(), false);

        $projects = $service->findInactiveProjects($query);
        $entries = $service->getProjectView($user, $projects, $now);

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
            'title' => 'report_inactive_project',
            'tableName' => 'inactive_project_reporting',
            'now' => $now,
            'skipColumns' => ['today', 'week', 'month', 'projectStart', 'projectEnd', 'comment'],
        ]);
    }
}
