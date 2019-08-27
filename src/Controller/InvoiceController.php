<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Invoice\InvoiceModel;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to create invoices and manage invoice templates.
 *
 * @Route(path="/invoice")
 * @Security("is_granted('view_invoice')")
 */
class InvoiceController extends AbstractController
{
    /**
     * @var ServiceInvoice
     */
    protected $service;
    /**
     * @var InvoiceTemplateRepository
     */
    protected $invoiceRepository;
    /**
     * @var TimesheetRepository
     */
    protected $timesheetRepository;
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTimeFactory;

    public function __construct(ServiceInvoice $service, InvoiceTemplateRepository $invoice, UserDateTimeFactory $dateTimeFactory)
    {
        $this->service = $service;
        $this->invoiceRepository = $invoice;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @Route(path="/", name="invoice", methods={"GET", "POST"})
     * @Security("is_granted('view_invoice')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexAction(Request $request, TimesheetRepository $repository)
    {
        if (!$this->invoiceRepository->hasTemplate()) {
            if ($this->isGranted('manage_invoice_template')) {
                return $this->redirectToRoute('admin_invoice_template_create');
            }
            $this->flashWarning('invoice.first_template');
        }

        $showPreview = false;
        $maxItemsPreview = 500;
        $entries = [];

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query, 'GET');
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if ($this->isGranted('create_invoice')) {
            if ($form->isValid()) {
                /** @var SubmitButton $createButton */
                $createButton = $form->get('create');
                if ($createButton->isClicked()) {
                    return $this->renderInvoice($query, $repository);
                }

                /** @var SubmitButton $previewButton */
                $previewButton = $form->get('preview');
                if ($previewButton->isClicked()) {
                    $showPreview = true;
                    $query->setPageSize($maxItemsPreview);
                    $entries = $this->getEntries($query, $repository);
                }
            }
        }

        $model = $this->prepareModel($query, $entries);

        return $this->render('invoice/index.html.twig', [
            'model' => $model,
            'form' => $form->createView(),
            'preview_max' => $maxItemsPreview,
            'preview_show' => $showPreview,
        ]);
    }

    protected function getDefaultQuery(): InvoiceQuery
    {
        $begin = $this->dateTimeFactory->createDateTime('first day of this month');
        $end = $this->dateTimeFactory->createDateTime('last day of this month');

        $query = new InvoiceQuery();
        $query->setOrder(InvoiceQuery::ORDER_ASC);
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setExported(InvoiceQuery::STATE_NOT_EXPORTED);
        $query->setState(InvoiceQuery::STATE_STOPPED);
        $query->setCurrentUser($this->getUser());

        return $query;
    }

    protected function renderInvoice(InvoiceQuery $query, TimesheetRepository $repository)
    {
        $entries = $this->getEntries($query, $repository);
        $model = $this->prepareModel($query, $entries);

        $document = $this->service->getDocumentByName($model->getTemplate()->getRenderer());
        if (null === $document) {
            throw new \Exception('Unknown invoice document: ' . $model->getTemplate()->getRenderer());
        }

        foreach ($this->service->getRenderer() as $renderer) {
            if ($renderer->supports($document)) {
                $response = $renderer->render($document, $model);
                if ($query->isMarkAsExported()) {
                    $repository->setExported($entries);
                }

                return $response;
            }
        }

        $this->flashError(
            sprintf('Cannot render invoice: %s (%s)', $model->getTemplate()->getRenderer(), $document->getName())
        );

        return $this->redirectToRoute('invoice');
    }

    /**
     * @param InvoiceQuery $query
     * @param TimesheetRepository $repository
     * @return Timesheet[]
     */
    protected function getEntries(InvoiceQuery $query, TimesheetRepository $repository): iterable
    {
        // customer needs to be defined, as we need the currency for the invoice
        if (null === $query->getCustomer()) {
            return [];
        }

        if (null === $query->getBegin()) {
            $query->setBegin($this->dateTimeFactory->createDateTime('first day of this month'));
        }
        if (null === $query->getEnd()) {
            $query->setEnd($this->dateTimeFactory->createDateTime('last day of this month'));
        }
        $query->getBegin()->setTime(0, 0, 0);
        $query->getEnd()->setTime(23, 59, 59);

        return $repository->getTimesheetsForQuery($query);
    }

    /**
     * @param InvoiceQuery $query
     * @param Timesheet[] $entries
     * @return InvoiceModel
     * @throws \Exception
     */
    protected function prepareModel(InvoiceQuery $query, array $entries): InvoiceModel
    {
        $model = new InvoiceModel();
        $model
            ->setQuery($query)
            ->setEntries($entries)
            ->setCustomer($query->getCustomer())
        ;

        if ($query->getTemplate() !== null) {
            $generator = $this->service->getNumberGeneratorByName($query->getTemplate()->getNumberGenerator());
            if (null === $generator) {
                throw new \Exception('Unknown number generator: ' . $query->getTemplate()->getNumberGenerator());
            }

            $calculator = $this->service->getCalculatorByName($query->getTemplate()->getCalculator());
            if (null === $calculator) {
                throw new \Exception('Unknown invoice calculator: ' . $query->getTemplate()->getCalculator());
            }

            $model->setTemplate($query->getTemplate());
            $model->setCalculator($calculator);
            $model->setNumberGenerator($generator);
        }

        return $model;
    }

    /**
     * @Route(path="/template", name="admin_invoice_template", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function listTemplateAction(): Response
    {
        $templates = $this->invoiceRepository->findByQuery(new BaseQuery());

        return $this->render('invoice/templates.html.twig', [
            'entries' => $templates,
        ]);
    }

    /**
     * @Route(path="/template/{id}/edit", name="admin_invoice_template_edit", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function editTemplateAction(InvoiceTemplate $template, Request $request): Response
    {
        return $this->renderTemplateForm($template, $request);
    }

    /**
     * @Route(path="/template/create", name="admin_invoice_template_create", methods={"GET", "POST"})
     * @Route(path="/template/create/{id}", name="admin_invoice_template_copy", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function createTemplateAction(Request $request, ?InvoiceTemplate $copyFrom): Response
    {
        if (!$this->invoiceRepository->hasTemplate()) {
            $this->flashWarning('invoice.first_template');
        }

        $template = new InvoiceTemplate();
        if (null !== $copyFrom) {
            $template
                ->setName('Copy of ' . $copyFrom->getName())
                ->setTitle($copyFrom->getTitle())
                ->setDueDays($copyFrom->getDueDays())
                ->setCalculator($copyFrom->getCalculator())
                ->setVat($copyFrom->getVat())
                ->setRenderer($copyFrom->getRenderer())
                ->setCompany($copyFrom->getCompany())
                ->setPaymentTerms($copyFrom->getPaymentTerms())
                ->setAddress($copyFrom->getAddress())
                ->setNumberGenerator($copyFrom->getNumberGenerator())
            ;
        }

        return $this->renderTemplateForm($template, $request);
    }

    /**
     * @Route(path="/template/{id}/delete", name="admin_invoice_template_delete", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function deleteTemplate(InvoiceTemplate $template, Request $request): Response
    {
        try {
            $this->invoiceRepository->removeTemplate($template);
            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('admin_invoice_template');
    }

    protected function renderTemplateForm(InvoiceTemplate $template, Request $request): Response
    {
        $editForm = $this->createEditForm($template);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->invoiceRepository->saveTemplate($template);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_invoice_template');
            } catch (\Exception $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('invoice/template_edit.html.twig', [
            'template' => $template,
            'form' => $editForm->createView()
        ]);
    }

    protected function getToolbarForm(InvoiceQuery $query, string $method): FormInterface
    {
        return $this->createForm(InvoiceToolbarForm::class, $query, [
            'action' => $this->generateUrl('invoice', []),
            'method' => $method,
            'attr' => [
                'id' => 'invoice-print-form'
            ],
        ]);
    }

    private function createEditForm(InvoiceTemplate $template): FormInterface
    {
        if ($template->getId() === null) {
            $url = $this->generateUrl('admin_invoice_template_create');
        } else {
            $url = $this->generateUrl('admin_invoice_template_edit', ['id' => $template->getId()]);
        }

        return $this->createForm(InvoiceTemplateForm::class, $template, [
            'action' => $url,
            'method' => 'POST'
        ]);
    }
}
