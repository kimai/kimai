<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Export\InitialTimeRangeFactory;
use App\Export\ServiceExport;
use App\Form\Toolbar\ExportToolbarForm;
use App\Repository\Query\ExportQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
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
     * @var ServiceExport
     */
    private $export;

    /**
     * @var InitialTimeRangeFactory
     */
    private $initialTimeRangeFactory;

    public function __construct(ServiceExport $export, InitialTimeRangeFactory $initialTimeRangeFactory)
    {
        $this->export = $export;
        $this->initialTimeRangeFactory = $initialTimeRangeFactory;
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
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('export');
        }

        $byCustomer = [];

        if ($form->isValid() && ($query->hasBookmark() || $request->query->has('performSearch'))) {
            $showPreview = true;
            $entries = $this->getEntries($query);
            foreach ($entries as $entry) {
                $cid = $entry->getProject()->getCustomer()->getId();
                if (!isset($byCustomer[$cid])) {
                    $byCustomer[$cid] = [
                        'customer' => $entry->getProject()->getCustomer(),
                        'rate' => 0,
                        'internalRate' => 0,
                        'duration' => 0,
                    ];
                }
                $byCustomer[$cid]['rate'] += $entry->getRate();
                $byCustomer[$cid]['internalRate'] += $entry->getInternalRate();
                $byCustomer[$cid]['duration'] += $entry->getDuration();
            }
        }

        return $this->render('export/index.html.twig', [
            'by_customer' => $byCustomer,
            'query' => $query,
            'entries' => $entries,
            'form' => $form->createView(),
            'renderer' => $this->export->getRenderer(),
            'preview_limit' => $maxItemsPreview,
            'preview_show' => $showPreview,
            'decimal' => $this->getUser()->isExportDecimal(),
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

        $type = $query->getRenderer();
        if (null === $type) {
            throw $this->createNotFoundException('Missing export renderer');
        }

        $renderer = $this->export->getRendererById($type);

        if (null === $renderer) {
            throw $this->createNotFoundException('Unknown export renderer');
        }

        $entries = $this->getEntries($query);
        $response = $renderer->render($entries, $query);

        // TODO check entries if user is allowed to update export state - see https://github.com/kevinpapst/kimai2/issues/1473
        if ($query->isMarkAsExported()) {
            $this->export->setExported($entries);
        }

        return $response;
    }

    protected function getDefaultQuery(): ExportQuery
    {
        [$begin, $end] = $this->initialTimeRangeFactory->getRange($this->getUser());

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

        return $this->export->getExportItems($query);
    }

    protected function getToolbarForm(ExportQuery $query, string $method): FormInterface
    {
        return $this->createForm(ExportToolbarForm::class, $query, [
            'action' => $this->generateUrl('export', []),
            'include_user' => $this->isGranted('view_other_timesheet'),
            'method' => $method,
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'export-form'
            ]
        ]);
    }
}
