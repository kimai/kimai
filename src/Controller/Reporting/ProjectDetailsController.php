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
use App\Reporting\ProjectDetails\ProjectDetailsForm;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use App\Utils\PageSetup;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProjectDetailsController extends AbstractController
{
    #[Route(path: '/reporting/project_details', name: 'report_project_details', methods: ['GET'])]
    #[IsGranted('report:project')]
    #[IsGranted(new Expression("is_granted('details', 'project')"))]
    public function __invoke(Request $request, ProjectStatisticService $service): Response
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ProjectDetailsQuery($dateFactory->createDateTime(), $user);
        $form = $this->createFormForGetRequest(ProjectDetailsForm::class, $query);
        $form->submit($request->query->all(), false);

        $projectView = null;
        $projectDetails = null;
        $project = $query->getProject();

        if ($project !== null && $this->isGranted('details', $project)) {
            $projectViews = $service->getProjectView($user, [$project], $query->getToday());
            $projectView = $projectViews[0];
            $projectDetails = $service->getProjectsDetails($query);
        }

        $page = new PageSetup('projects');
        $page->setHelp('project.html');

        if ($project !== null) {
            $page->setActionName('project');
            $page->setActionView('project_details_report');
            $page->setActionPayload(['project' => $project]);
        }

        return $this->render('reporting/project_details.html.twig', [
            'page_setup' => $page,
            'report_title' => 'report_project_details',
            'project' => $project,
            'project_view' => $projectView,
            'project_details' => $projectDetails,
            'form' => $form->createView(),
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }
}
