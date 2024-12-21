<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Project\ProjectStatisticService;
use App\Reporting\ProjectView\ProjectViewForm;
use App\Reporting\ProjectView\ProjectViewQuery;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/project_view')]
final class ProjectViewController extends AbstractController
{
    #[Route(path: '', name: 'report_project_view', methods: ['GET', 'POST'])]
    #[IsGranted('report:project')]
    #[IsGranted(new Expression("is_granted('budget_any', 'project')"))]
    public function __invoke(Request $request, ProjectStatisticService $service): Response
    {
        $data = $this->getData($request, $service);

        return $this->render('reporting/project_view.html.twig', array_merge($data, [
            'report_title' => 'report_project_view',
            'tableName' => 'project_view_reporting',
            'export_route' => 'report_project_view_export',
        ]));
    }

    /**
     * @param Request $request
     * @param ProjectStatisticService $service
     * @return array{'entries': array<mixed>, 'form': FormView, 'now': \DateTimeInterface}
     */
    private function getData(Request $request, ProjectStatisticService $service): array
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ProjectViewQuery($dateFactory->createDateTime(), $user);
        $form = $this->createFormForGetRequest(ProjectViewForm::class, $query);
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

        return [
            'entries' => $byCustomer,
            'form' => $form->createView(),
            'now' => $dateFactory->createDateTime(),
        ];
    }

    #[Route(path: '/export', name: 'report_project_view_export', methods: ['GET', 'POST'])]
    public function export(Request $request, ProjectStatisticService $service): Response
    {
        $data = $this->getData($request, $service);

        $content = $this->renderView('reporting/project_list_export.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-project-overview');

        return $writer->getFileResponse($spreadsheet);
    }
}
