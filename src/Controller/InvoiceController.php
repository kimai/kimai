<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Form\InvoiceDocumentUploadForm;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Toolbar\InvoiceToolbarSimpleForm;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceQuery;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Controller used to create invoices and manage invoice templates.
 *
 * @Route(path="/invoice")
 * @Security("is_granted('view_invoice')")
 */
final class InvoiceController extends AbstractController
{
    /**
     * @var ServiceInvoice
     */
    private $service;
    /**
     * @var InvoiceTemplateRepository
     */
    private $templateRepository;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTimeFactory;
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(ServiceInvoice $service, InvoiceTemplateRepository $templateRepository, InvoiceRepository $invoiceRepository, UserDateTimeFactory $dateTimeFactory, EventDispatcherInterface $dispatcher)
    {
        $this->service = $service;
        $this->templateRepository = $templateRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route(path="/", name="invoice", methods={"GET", "POST"})
     * @Security("is_granted('view_invoice')")
     */
    public function indexAction(Request $request, SystemConfiguration $configuration): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            if ($this->isGranted('manage_invoice_template')) {
                return $this->redirectToRoute('admin_invoice_template_create');
            }
            $this->flashWarning('invoice.first_template');
        }

        $showPreview = false;
        $model = null;

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query, $configuration->find('invoice.simple_form'));
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if ($this->isGranted('create_invoice') && $form->isValid()) {
            try {
                /** @var SubmitButton $createButton */
                $createButton = $form->get('create');
                if ($createButton->isClicked()) {
                    return $this->renderInvoice($query);
                }

                /** @var SubmitButton $printButton */
                $printButton = $form->get('print');
                if ($printButton->isClicked()) {
                    return $this->service->renderInvoice($query, $this->dispatcher);
                }
            } catch (\Exception $ex) {
                $this->logException($ex);
                $this->flashError('action.update.error', ['%reason%' => 'check doctor/logs']);
            }

            /** @var SubmitButton $previewButton */
            $previewButton = $form->get('preview');
            if ($previewButton->isClicked()) {
                $showPreview = true;
            }
        }

        try {
            $model = $this->service->createModel($query);
            if ($showPreview) {
                $entries = $this->service->findInvoiceItems($query);
                if (!empty($entries)) {
                    $model->addEntries($entries);
                }
            }
        } catch (\Exception $ex) {
            $this->logException($ex);
            $this->flashError($ex->getMessage());
            $showPreview = false;
        }

        return $this->render('invoice/index.html.twig', [
            'query' => $query,
            'model' => $model,
            'form' => $form->createView(),
            'preview' => $showPreview,
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
        // limit access to data from teams
        $query->setCurrentUser($this->getUser());

        if (!$this->isGranted('view_other_timesheet')) {
            // limit access to own data
            $query->setUser($this->getUser());
        }

        return $query;
    }

    protected function renderInvoice(InvoiceQuery $query)
    {
        try {
            $invoice = $this->service->createInvoice($query, $this->dispatcher);

            $this->flashSuccess('action.update.success');

            if ($this->isGranted('history_invoice')) {
                return $this->redirectToRoute('admin_invoice_list', ['id' => $invoice->getId()]);
            }

            $file = $this->service->getInvoiceFile($invoice);

            return $this->file($file->getRealPath(), $file->getBasename());
        } catch (\Exception $ex) {
            $this->flashError($ex->getMessage());
        }

        return $this->redirectToRoute('invoice');
    }

    /**
     * @Route(path="/change-status/{id}/{status}", name="admin_invoice_status", methods={"GET"})
     * @Security("is_granted('history_invoice')")
     */
    public function changeStatusAction(Invoice $invoice, string $status): Response
    {
        try {
            $this->service->changeInvoiceStatus($invoice, $status);
            $this->flashSuccess('action.update.success');
        } catch (\Exception $ex) {
            $this->flashError('action.update.error');
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    /**
     * @Route(path="/delete/{id}", name="admin_invoice_delete", methods={"GET"})
     * @Security("is_granted('history_invoice')")
     */
    public function deleteInvoiceAction(Invoice $invoice): Response
    {
        try {
            $this->service->deleteInvoice($invoice);
            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error');
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    /**
     * @Route(path="/download/{id}", name="admin_invoice_download", methods={"GET"})
     * @Security("is_granted('history_invoice')")
     */
    public function downloadAction(Invoice $invoice): Response
    {
        $file = $this->service->getInvoiceFile($invoice);

        if (null === $file) {
            throw $this->createNotFoundException(
                sprintf('Invoice file "%s" could not be found for invoice ID "%s"', $invoice->getInvoiceFilename(), $invoice->getId())
            );
        }

        return $this->file($file->getRealPath(), $file->getBasename());
    }

    /**
     * @Route(path="/show/{page}", defaults={"page": 1}, requirements={"page": "[1-9]\d*"}, name="admin_invoice_list", methods={"GET"})
     * @Security("is_granted('history_invoice')")
     */
    public function showInvoicesAction(Request $request, int $page): Response
    {
        $invoice = null;

        if (null !== ($id = $request->get('id'))) {
            $invoice = $this->invoiceRepository->find($id);
        }

        $query = new InvoiceQuery();
        $query->setOrderBy('date');
        $query->setPage($page);
        $query->setCurrentUser($this->getUser());

        $invoices = $this->invoiceRepository->getPagerfantaForQuery($query);

        return $this->render('invoice/listing.html.twig', [
            'entries' => $invoices,
            'query' => $query,
            'download' => $invoice,
        ]);
    }

    /**
     * @Route(path="/template", name="admin_invoice_template", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function listTemplateAction(): Response
    {
        $templates = $this->templateRepository->getPagerfantaForQuery(new BaseQuery());

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
     * @Route(path="/document_upload", name="admin_invoice_document_upload", methods={"GET", "POST"})
     * @Security("is_granted('upload_invoice_template')")
     */
    public function uploadDocumentAction(Request $request, string $projectDirectory, InvoiceDocumentRepository $documentRepository)
    {
        $dir = $documentRepository->getCustomInvoiceDirectory();
        $invoiceDir = $projectDirectory . DIRECTORY_SEPARATOR . $dir;
        $canUpload = true;
        $form = null;

        if (!file_exists($invoiceDir)) {
            @mkdir($invoiceDir);
        }
        if (!file_exists($invoiceDir)) {
            $this->flashError(sprintf('Invoice directory is not existing and could not be created: %s', $dir));
            $canUpload = false;
        }
        if (!is_writable($invoiceDir)) {
            $this->flashError(sprintf('Invoice directory cannot be written: %s', $dir));
            $canUpload = false;
        }

        if ($canUpload) {
            $form = $this->createForm(InvoiceDocumentUploadForm::class, null, [
                'action' => $this->generateUrl('admin_invoice_document_upload', []),
                'method' => 'POST'
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $form->get('document')->getData();

                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate(
                    'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                    $originalFilename
                );
                $newFilename = $safeFilename . '.' . $uploadedFile->guessExtension();

                try {
                    $uploadedFile->move($invoiceDir, $newFilename);
                    $this->flashSuccess('action.update.success');

                    return $this->redirectToRoute('admin_invoice_document_upload');
                } catch (\Exception $e) {
                    $this->flashError(
                        sprintf('Failed uploading invoice document: %e', $e->getMessage())
                    );
                }
            }
        }

        return $this->render('invoice/document_upload.html.twig', [
            'form' => (null !== $form) ? $form->createView() : null,
            'documents' => $this->service->getDocuments(),
            'baseDirectory' => $projectDirectory . DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route(path="/template/create", name="admin_invoice_template_create", methods={"GET", "POST"})
     * @Route(path="/template/create/{id}", name="admin_invoice_template_copy", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function createTemplateAction(Request $request, ?InvoiceTemplate $copyFrom): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            $this->flashWarning('invoice.first_template');
        }

        $template = new InvoiceTemplate();

        if (null !== $copyFrom) {
            $template = clone $copyFrom;
            $template->setName('Copy of ' . $copyFrom->getName());
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
            $this->templateRepository->removeTemplate($template);
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
                $this->templateRepository->saveTemplate($template);
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

    protected function getToolbarForm(InvoiceQuery $query, bool $simple): FormInterface
    {
        $form = $simple ? InvoiceToolbarSimpleForm::class : InvoiceToolbarForm::class;

        return $this->createForm($form, $query, [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
            'include_user' => $this->isGranted('view_other_timesheet'),
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
