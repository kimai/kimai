<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Export\ServiceExport;
use App\Form\Toolbar\ExportToolbarForm;
use App\Repository\Query\ExportQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to export timesheet data.
 *
 * @Route(path="/export")
 * @Security("is_granted('create_export')")
 */
class ExportController extends AbstractController
{
    /**
     * @var TimesheetRepository
     */
    protected $timesheetRepository;

    /**
     * @var ServiceExport
     */
    protected $export;
    /**
     * @var UserDateTimeFactory
     */
    protected $dateFactory;

    /**
     * @param TimesheetRepository $timesheet
     * @param ServiceExport $export
     */
    public function __construct(TimesheetRepository $timesheet, ServiceExport $export, UserDateTimeFactory $dateTime)
    {
        $this->timesheetRepository = $timesheet;
        $this->export = $export;
        $this->dateFactory = $dateTime;
    }

    /**
     * @Route(path="/", name="export", methods={"GET"})
     */
    public function indexAction(Request $request): Response
    {
        $query = $this->getDefaultQuery();

        $showPreview = false;
        $maxItemsPreview = 500;
        $entries = [];

        $form = $this->getToolbarForm($query, 'GET');
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if ($form->isValid()) {
            /** @var SubmitButton $previewButton */
            $previewButton = $form->get('preview');
            if ($previewButton->isClicked()) {
                $showPreview = true;
                $query->setPageSize($maxItemsPreview);
                $entries = $this->getEntries($query);
            }
        }

        return $this->render('export/index.html.twig', [
            'query' => $query,
            'entries' => $entries,
            'form' => $form->createView(),
            'renderer' => $this->export->getRenderer(),
            'preview_max' => $maxItemsPreview,
            'preview_show' => $showPreview,
        ]);
    }

    /**
     * @Route(path="/data", name="export_data", methods={"POST"})
     */
    public function export(Request $request): Response
    {
        $query = $this->getDefaultQuery();

        $form = $this->getToolbarForm($query, 'POST');
        $form->handleRequest($request);

        $type = $query->getType();
        if (null === $type) {
            throw $this->createNotFoundException('Missing export renderer');
        }

        $renderer = $this->export->getRendererById($type);

        if (null === $renderer) {
            throw $this->createNotFoundException('Unknown export renderer');
        }

        $entries = $this->getEntries($query);
        $response = $renderer->render($entries, $query);

        if ($query->isMarkAsExported()) {
            $this->timesheetRepository->setExported($entries);
        }

        return $response;
    }

    protected function getDefaultQuery(): ExportQuery
    {
        $begin = $this->dateFactory->createDateTime('first day of this month 00:00:00');
        $end = $this->dateFactory->createDateTime('last day of this month 23:59:59');

        $query = new ExportQuery();
        $query->setOrder(ExportQuery::ORDER_ASC);
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setState(ExportQuery::STATE_STOPPED);
        $query->setExported(ExportQuery::STATE_NOT_EXPORTED);
        $query->setCurrentUser($this->getUser());

        return $query;
    }

    /**
     * @param ExportQuery $query
     * @return Timesheet[]
     */
    protected function getEntries(ExportQuery $query): array
    {
        if (null !== $query->getBegin()) {
            $query->getBegin()->setTime(0, 0, 0);
        }
        if (null !== $query->getEnd()) {
            $query->getEnd()->setTime(23, 59, 59);
        }

        return $this->timesheetRepository->getTimesheetsForQuery($query, true);
    }

    protected function getToolbarForm(ExportQuery $query, string $method): FormInterface
    {
        return $this->createForm(ExportToolbarForm::class, $query, [
            'action' => $this->generateUrl('export', []),
            'method' => $method,
            'attr' => [
                'id' => 'export-form'
            ]
        ]);
    }
}
