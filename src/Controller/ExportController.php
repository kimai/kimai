<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ExportableItem;
use App\Export\Base\DispositionInlineInterface;
use App\Export\ServiceExport;
use App\Export\TooManyItemsExportException;
use App\Form\Toolbar\ExportToolbarForm;
use App\Repository\Query\ExportQuery;
use App\Utils\PageSetup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to export timesheet data.
 */
#[Route(path: '/export')]
#[IsGranted('create_export')]
final class ExportController extends AbstractController
{
    public function __construct(private ServiceExport $export)
    {
    }

    #[Route(path: '/', name: 'export', methods: ['GET'])]
    public function indexAction(Request $request): Response
    {
        $query = $this->getDefaultQuery();

        $showPreview = false;
        $tooManyResults = false;
        $maxItemsPreview = 500;
        $entries = [];

        $form = $this->getToolbarForm($query, 'GET');
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('export');
        }

        $byCustomer = [];

        if ($form->isValid() && ($query->hasBookmark() || $request->query->has('performSearch'))) {
            try {
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
                    $byCustomer[$cid]['internalRate'] += $entry->getInternalRate() ?? 0.0;
                    $byCustomer[$cid]['duration'] += $entry->getDuration() ?? 0;
                }
            } catch (TooManyItemsExportException $ex) {
                $tooManyResults = true;
                $showPreview = false;
                $entries = [];
                $this->logException($ex);
            }
        }

        $page = new PageSetup('export');
        $page->setHelp('export.html');

        return $this->render('export/index.html.twig', [
            'page_setup' => $page,
            'too_many' => $tooManyResults,
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

    #[Route(path: '/data', name: 'export_data', methods: ['POST'])]
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

        // display file inline if supported and `markAsExported` is not set
        if ($renderer instanceof DispositionInlineInterface && !$query->isMarkAsExported()) {
            $renderer->setDispositionInline(true);
        }

        $entries = $this->getEntries($query);
        $response = $renderer->render($entries, $query);

        if ($query->isMarkAsExported()) {
            $this->export->setExported($entries);
        }

        return $response;
    }

    private function getDefaultQuery(): ExportQuery
    {
        $begin = $this->getDateTimeFactory()->getStartOfMonth();
        $end = $this->getDateTimeFactory()->getEndOfMonth();

        $query = new ExportQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setCurrentUser($this->getUser());

        return $query;
    }

    /**
     * @param ExportQuery $query
     * @return ExportableItem[]
     * @throws TooManyItemsExportException
     */
    private function getEntries(ExportQuery $query): array
    {
        if (null !== $query->getBegin()) {
            $query->getBegin()->setTime(0, 0, 0);
        }
        if (null !== $query->getEnd()) {
            $query->getEnd()->setTime(23, 59, 59);
        }

        return $this->export->getExportItems($query);
    }

    /**
     * @param ExportQuery $query
     * @param string $method
     * @return FormInterface<ExportQuery>
     */
    private function getToolbarForm(ExportQuery $query, string $method): FormInterface
    {
        return $this->createSearchForm(ExportToolbarForm::class, $query, [
            'action' => $this->generateUrl('export', []),
            'include_user' => $this->isGranted('view_other_timesheet'),
            'include_export' => $this->isGranted('edit_export_other_timesheet'),
            'method' => $method,
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'export-form'
            ]
        ]);
    }
}
