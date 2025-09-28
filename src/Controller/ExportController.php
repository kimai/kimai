<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\ExportableItem;
use App\Entity\ExportTemplate;
use App\Export\Base\DispositionInlineInterface;
use App\Export\ServiceExport;
use App\Export\TooManyItemsExportException;
use App\Form\ExportTemplateSpreadsheetForm;
use App\Form\Toolbar\ExportToolbarForm;
use App\Repository\ExportTemplateRepository;
use App\Repository\Query\ExportQuery;
use App\Utils\PageSetup;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to export timesheet data.
 */
#[Route(path: '/export')]
#[IsGranted('create_export')]
final class ExportController extends AbstractController
{
    public function __construct(private readonly ServiceExport $export)
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

        $buttons = [];
        foreach ($this->export->getRenderer() as $renderer) {
            if (method_exists($renderer, 'getType')) {
                $class = $renderer->getType();
            } else {
                // TODO remove me with 3.0
                $class = \get_class($renderer);
                $pos = strrpos($class, '\\');
                if ($pos !== false) {
                    $class = substr($class, $pos + 1);
                }
                $class = strtolower(str_replace('Renderer', '', $class));
            }
            $buttons[$class][$renderer->getId()] = [
                'title' => $renderer->getTitle(),
                'internal' => method_exists($renderer, 'isInternal') ? $renderer->isInternal() : false,
            ];
        }

        if ($this->isGranted('view_other_timesheet')) {
            $showRates = $this->isGranted('view_rate_other_timesheet');
        } else {
            $showRates = $this->isGranted('view_rate_own_timesheet');
        }

        return $this->render('export/index.html.twig', [
            'page_setup' => $page,
            'too_many' => $tooManyResults,
            'by_customer' => $byCustomer,
            'query' => $query,
            'entries' => $entries,
            'form' => $form->createView(),
            'buttons' => $buttons,
            'preview_limit' => $maxItemsPreview,
            'preview_show' => $showPreview,
            'show_rates' => $showRates,
        ]);
    }

    #[Route(path: '/data', name: 'export_data', methods: ['POST'])]
    public function export(Request $request, SystemConfiguration $systemConfiguration): Response
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

        $oldMaxExecTime = \ini_get('max_execution_time');
        ini_set('max_execution_time', $systemConfiguration->getExportTimeout());

        // display file inline if supported and `markAsExported` is not set
        if ($renderer instanceof DispositionInlineInterface && !$query->isMarkAsExported()) {
            $renderer->setDispositionInline(true);
        }

        $entries = $this->getEntries($query);
        $response = $renderer->render($entries, $query);

        if ($query->isMarkAsExported()) {
            $this->export->setExported($entries);
        }

        ini_set('max_execution_time', $oldMaxExecTime);

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

    #[Route(path: '/template-create', name: 'export_template_create', methods: ['GET', 'POST'])]
    public function createExportTemplate(Request $request, ExportTemplateRepository $repository): Response
    {
        return $this->editExportForm($this->generateUrl('export_template_create'), $request, $repository, new ExportTemplate());
    }

    #[Route(path: '/template-edit/{exportTemplate}', name: 'export_template_edit', methods: ['GET', 'POST'])]
    public function editExportTemplate(ExportTemplate $exportTemplate, Request $request, ExportTemplateRepository $repository): Response
    {
        return $this->editExportForm($this->generateUrl('export_template_edit', ['exportTemplate' => $exportTemplate->getId()]), $request, $repository, $exportTemplate);
    }

    private function editExportForm(string $url, Request $request, ExportTemplateRepository $repository, ExportTemplate $exportTemplate): Response
    {
        $form = $this->createForm(ExportTemplateSpreadsheetForm::class, $exportTemplate, [
            'action' => $url,
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $repository->saveExportTemplate($exportTemplate);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('export');
            } catch (\Exception $ex) {
                $this->handleFormUpdateException($ex, $form);
            }
        }

        return $this->render('export/template.html.twig', [
            'form' => $form->createView(),
            'template' => $exportTemplate,
        ]);
    }
}
