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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to export timesheet data.
 *
 * @Route(path="/export")
 * @Security("is_granted('view_export')")
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
     * @return ExportQuery
     * @throws \Exception
     */
    protected function getDefaultQuery()
    {
        $begin = $this->dateFactory->createDateTime('first day of this month 00:00:00');
        $end = $this->dateFactory->createDateTime('last day of this month 23:59:59');

        $query = new ExportQuery();
        $query->setOrder(ExportQuery::ORDER_ASC);
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setState(ExportQuery::STATE_STOPPED);
        $query->setExported(ExportQuery::STATE_NOT_EXPORTED);

        return $query;
    }

    /**
     * @Route(path="/", name="export", methods={"GET", "POST"})
     * @Security("is_granted('view_export')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExportQuery $query */
            $query = $form->getData();
        }

        $entries = $this->getEntries($query);

        return $this->render('export/index.html.twig', [
            'entries' => $entries,
            'form' => $form->createView(),
            'renderer' => $this->export->getRenderer(),
        ]);
    }

    /**
     * @Route(path="/data", name="export_data", methods={"GET", "POST"})
     * @Security("is_granted('create_export')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function export(Request $request)
    {
        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ExportQuery $query */
            $query = $form->getData();
        }

        $type = $query->getType();
        if (null === $type) {
            throw $this->createNotFoundException('Missing export renderer');
        }

        $renderer = $this->export->getRendererById($type);

        // this code should not be reached, as the query already filters invalid values
        // when trying to call setType() with an unknown value
        if (null === $renderer) {
            throw $this->createNotFoundException('Unknown export renderer');
        }

        $entries = $this->getEntries($query);

        return $renderer->render($entries, $query);
    }

    /**
     * @param ExportQuery $query
     * @return Timesheet[]
     */
    protected function getEntries(ExportQuery $query)
    {
        $query->setResultType(ExportQuery::RESULT_TYPE_QUERYBUILDER);
        $query->getBegin()->setTime(0, 0, 0);
        $query->getEnd()->setTime(23, 59, 59);

        $queryBuilder = $this->timesheetRepository->findByQuery($query);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param ExportQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(ExportQuery $query)
    {
        return $this->createForm(ExportToolbarForm::class, $query, [
            'action' => $this->generateUrl('export', []),
            'method' => 'POST',
            'attr' => [
                'id' => 'export-form'
            ]
        ]);
    }
}
