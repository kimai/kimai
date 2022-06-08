<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Project\ProjectStatisticService;
use App\Reporting\ProjectDetails\ProjectDetailsForm;
use App\Reporting\ProjectDetails\ProjectDetailsQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ProjectDetailsController extends AbstractController
{
    /**
     * @Route(path="/reporting/project_details", name="report_project_details", methods={"GET"})
     * @Security("is_granted('view_reporting') and is_granted('details', 'project')")
     */
    public function __invoke(Request $request, ProjectStatisticService $service)
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ProjectDetailsQuery($dateFactory->createDateTime(), $user);
        $form = $this->createForm(ProjectDetailsForm::class, $query);
        $form->submit($request->query->all(), false);

        $projectView = null;
        $projectDetails = null;

        if ($query->getProject() !== null && $this->isGranted('details', $query->getProject())) {
            $projectViews = $service->getProjectView($user, [$query->getProject()], $query->getToday());
            $projectView = $projectViews[0];
            $projectDetails = $service->getProjectsDetails($query);
        }

        return $this->render('reporting/project_details.html.twig', [
            'project' => $query->getProject(),
            'project_view' => $projectView,
            'project_details' => $projectDetails,
            'form' => $form->createView(),
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }
}
