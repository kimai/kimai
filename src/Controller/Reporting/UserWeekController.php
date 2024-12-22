<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Model\DailyStatistic;
use App\Reporting\WeekByUser\WeekByUser;
use App\Reporting\WeekByUser\WeekByUserForm;
use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/user')]
#[IsGranted('report:user')]
final class UserWeekController extends AbstractUserReportController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route(path: '/week', name: 'report_user_week', methods: ['GET', 'POST'])]
    public function weekByUser(Request $request): Response
    {
        return $this->render('reporting/report_by_user.html.twig', $this->getData($request));
    }

    #[Route(path: '/week_export', name: 'report_user_week_export', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        $data = $this->getData($request, true);

        $content = $this->renderView('reporting/report_by_user_data.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-user-weekly');

        return $writer->getFileResponse($spreadsheet);
    }

    private function getData(Request $request, bool $export = false): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new WeekByUser();
        $values->setDecimal($export);
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfWeek());

        $form = $this->createFormForGetRequest(WeekByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($values->getUser() === null) {
            $values->setUser($currentUser);
        }

        if ($currentUser !== $values->getUser() && !$canChangeUser) {
            throw new AccessDeniedException('User is not allowed to see other users timesheet');
        }

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfWeek());
        }

        $start = $dateTimeFactory->getStartOfWeek($values->getDate());
        $end = $dateTimeFactory->getEndOfWeek($values->getDate());
        $selectedUser = $values->getUser();

        $previous = clone $start;
        $previous->modify('-1 week');

        $next = clone $start;
        $next->modify('+1 week');

        $data = $this->prepareReport($start, $end, $selectedUser);

        return [
            'decimal' => $values->isDecimal(),
            'dataType' => $values->getSumType(),
            'report_title' => 'report_user_week',
            'box_id' => 'user-week-reporting-box',
            'form' => $form->createView(),
            'period' => new DailyStatistic($start, $end, $selectedUser),
            'rows' => $data,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
            'begin' => $start,
            'end' => $end,
            'export_route' => 'report_user_week_export',
        ];
    }
}
