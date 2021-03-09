<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Reporting\ProjectView\ProjectViewForm;
use App\Reporting\ProjectView\ProjectViewQuery;
use App\Reporting\ProjectView\ProjectViewService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ProjectViewController extends AbstractController
{
    /**
     * @Route(path="/reporting/project_view", name="report_project_view", methods={"GET","POST"})
     * @Security("is_granted('view_reporting') and is_granted('budget_project')")
     */
    public function __invoke(Request $request, ProjectViewService $service)
    {
        $query = new ProjectViewQuery($this->getDateTimeFactory()->createDateTime(), $this->getUser());

        $form = $this->createForm(ProjectViewForm::class, $query, [
            'action' => $this->generateUrl('report_project_view')
        ]);

        $form->submit($request->query->all(), false);

        $entries = $service->getProjectView($query);

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
        ]);
    }
}
