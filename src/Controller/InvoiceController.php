<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Export\Spreadsheet\AnnotatedObjectExporter;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Form\InvoiceDocumentUploadForm;
use App\Form\InvoicePaymentDateForm;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceArchiveForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Toolbar\InvoiceToolbarSimpleForm;
use App\Invoice\ServiceInvoice;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceArchiveQuery;
use App\Repository\Query\InvoiceQuery;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
     * @var InvoiceRepository
     */
    private $invoiceRepository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(ServiceInvoice $service, InvoiceTemplateRepository $templateRepository, InvoiceRepository $invoiceRepository, EventDispatcherInterface $dispatcher)
    {
        $this->service = $service;
        $this->templateRepository = $templateRepository;
        $this->invoiceRepository = $invoiceRepository;
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

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query, $configuration->find('invoice.simple_form'));
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('invoice');
        }

        $models = [];
        $total = 0;
        $searched = false;

        if ($form->isValid() && $this->isGranted('create_invoice')) {
            if ($request->query->has('createInvoice')) {
                try {
                    return $this->renderInvoice($query, $request);
                } catch (Exception $ex) {
                    $this->logException($ex);
                    $this->flashError('action.update.error', ['%reason%' => 'check doctor/logs']);
                }
            }

            if ($form->get('template')->getData() !== null) {
                try {
                    $models = $this->service->createModels($query);
                    $searched = true;
                } catch (Exception $ex) {
                    $this->logException($ex);
                    $this->flashError($ex->getMessage());
                }
            }
        }

        foreach ($models as $model) {
            $total += \count($model->getCalculator()->getEntries());
        }

        return $this->render('invoice/index.html.twig', [
            'models' => $models,
            'form' => $form->createView(),
            'limit_preview' => ($total > 500),
            'searched' => $searched,
        ]);
    }

    /**
     * @Route(path="/preview/{customer}/{template}", name="invoice_preview", methods={"GET"})
     * @Security("is_granted('view_invoice')")
     */
    public function previewAction(Customer $customer, InvoiceTemplate $template, Request $request, SystemConfiguration $configuration): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            return $this->redirectToRoute('invoice');
        }

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query, $configuration->find('invoice.simple_form'));
        $form->submit($request->query->all(), false);

        if ($form->isValid() && $this->isGranted('create_invoice')) {
            try {
                $query->setTemplate($template);
                $query->setCustomers([$customer]);
                $model = $this->service->createModel($query);

                return $this->service->renderInvoiceWithModel($model, $this->dispatcher);
            } catch (Exception $ex) {
                $this->logException($ex);
                $this->flashError('action.update.error', ['%reason%' => 'Failed generating invoice preview: ' . $ex->getMessage()]);
            }
        }

        return $this->redirectToRoute('invoice');
    }

    /**
     * @Route(path="/save-invoice/{customer}/{template}", name="invoice_create", methods={"GET"})
     * @Security("is_granted('view_invoice')")
     */
    public function createInvoiceAction(Customer $customer, InvoiceTemplate $template, Request $request, SystemConfiguration $configuration): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            return $this->redirectToRoute('invoice');
        }

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query, $configuration->find('invoice.simple_form'));
        $form->submit($request->query->all(), false);

        if ($form->isValid() && $this->isGranted('create_invoice')) {
            $query->setTemplate($template);
            $query->setCustomers([$customer]);

            return $this->renderInvoice($query, $request);
        }

        return $this->redirectToRoute('invoice');
    }

    private function getDefaultQuery(): InvoiceQuery
    {
        $factory = $this->getDateTimeFactory();
        $begin = $factory->getStartOfMonth();
        $end = $factory->getEndOfMonth();

        $query = new InvoiceQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        // limit access to data from teams
        $query->setCurrentUser($this->getUser());

        if (!$this->isGranted('view_other_timesheet')) {
            // limit access to own data
            $query->setUser($this->getUser());
        }

        return $query;
    }

    private function renderInvoice(InvoiceQuery $query, Request $request)
    {
        // use the current request locale as fallback, if no translation was configured
        if (null !== $query->getTemplate() && null === $query->getTemplate()->getLanguage()) {
            $query->getTemplate()->setLanguage($request->getLocale());
        }

        try {
            $invoices = $this->service->createInvoices($query, $this->dispatcher);

            $this->flashSuccess('action.update.success');

            if (\count($invoices) === 1) {
                return $this->redirectToRoute('admin_invoice_list', ['id' => $invoices[0]->getId()]);
            }

            return $this->redirectToRoute('admin_invoice_list');
        } catch (Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('invoice');
    }

    /**
     * @Route(path="/change-status/{id}/{status}", name="admin_invoice_status", methods={"GET", "POST"})
     */
    public function changeStatusAction(Invoice $invoice, string $status, Request $request): Response
    {
        if ($status === Invoice::STATUS_PAID) {
            $form = $this->createPaymentDateForm($invoice, $status);
            $form->handleRequest($request);

            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->render('invoice/payment_date_edit.html.twig', [
                    'invoice' => $invoice,
                    'form' => $form->createView()
                ]);
            }
        }

        try {
            $this->service->changeInvoiceStatus($invoice, $status);
            $this->flashSuccess('action.update.success');
        } catch (Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    /**
     * @Route(path="/delete/{id}/{token}", name="admin_invoice_delete", methods={"GET"})
     */
    public function deleteInvoiceAction(Invoice $invoice, string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.delete', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_list');
        }

        $csrfTokenManager->refreshToken($token);

        try {
            $this->service->deleteInvoice($invoice);
            $this->flashSuccess('action.delete.success');
        } catch (Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    /**
     * @Route(path="/download/{id}", name="admin_invoice_download", methods={"GET"})
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
     */
    public function showInvoicesAction(Request $request, int $page): Response
    {
        $invoice = null;

        if (null !== ($id = $request->get('id'))) {
            $invoice = $this->invoiceRepository->find($id);
        }

        $query = new InvoiceArchiveQuery();
        $query->setPage($page);
        $query->setCurrentUser($this->getUser());

        $form = $this->getArchiveToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('admin_invoice_list');
        }

        $invoices = $this->invoiceRepository->getPagerfantaForQuery($query);

        return $this->render('invoice/listing.html.twig', [
            'entries' => $invoices,
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'download' => $invoice,
        ]);
    }

    /**
     * @Route(path="/export", name="invoice_export", methods={"GET"})
     */
    public function exportAction(Request $request, AnnotatedObjectExporter $exporter)
    {
        $query = new InvoiceArchiveQuery();
        $query->setCurrentUser($this->getUser());

        $form = $this->getArchiveToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        $entries = $this->invoiceRepository->getInvoicesForQuery($query);

        $spreadsheet = $exporter->export(Invoice::class, $entries);
        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-invoices');

        return $writer->getFileResponse($spreadsheet);
    }

    /**
     * @Route(path="/template/{page}", requirements={"page": "[1-9]\d*"}, defaults={"page": 1}, name="admin_invoice_template", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function listTemplateAction(int $page): Response
    {
        $query = new BaseQuery();
        $query->setPage($page);

        $templates = $this->templateRepository->getPagerfantaForQuery($query);

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
        $dir = $documentRepository->getUploadDirectory();
        $invoiceDir = $dir;

        // do not execute realpath, as it will return an empty string if the invoice directory is NOT existing!
        if ($invoiceDir[0] !== '/') {
            $invoiceDir = $projectDirectory . DIRECTORY_SEPARATOR . $dir;
        }

        $canUpload = true;

        if (!file_exists($invoiceDir)) {
            @mkdir($invoiceDir, 0777);
        }

        if (!is_dir($invoiceDir)) {
            $this->flashError(sprintf('Invoice directory "%s" is not existing and could not be created.', $dir));
            $canUpload = false;
        } elseif (!is_writable($invoiceDir)) {
            $this->flashError(sprintf('Invoice directory "%s" cannot be written.', $dir));
            $canUpload = false;
        }

        $form = $this->createForm(InvoiceDocumentUploadForm::class, null, [
            'action' => $this->generateUrl('admin_invoice_document_upload', []),
            'method' => 'POST'
        ]);

        if ($canUpload) {
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
                } catch (Exception $ex) {
                    $this->flashException($ex, 'action.upload.error');
                }
            }
        }

        return $this->render('invoice/document_upload.html.twig', [
            'form' => $form->createView(),
            'documents' => $this->service->getDocuments(true),
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
        $template = new InvoiceTemplate();

        if (null !== $copyFrom) {
            $template = clone $copyFrom;
            $template->setName('Copy of ' . $copyFrom->getName());
        }

        return $this->renderTemplateForm($template, $request);
    }

    /**
     * @Route(path="/template/{id}/delete/{token}", name="admin_invoice_template_delete", methods={"GET", "POST"})
     * @Security("is_granted('manage_invoice_template')")
     */
    public function deleteTemplate(InvoiceTemplate $template, string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.delete_template', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_template');
        }

        $csrfTokenManager->refreshToken($token);

        try {
            $this->templateRepository->removeTemplate($template);
            $this->flashSuccess('action.delete.success');
        } catch (Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_invoice_template');
    }

    private function renderTemplateForm(InvoiceTemplate $template, Request $request): Response
    {
        $editForm = $this->createEditForm($template);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->templateRepository->saveTemplate($template);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_invoice_template');
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        return $this->render('invoice/template_edit.html.twig', [
            'template' => $template,
            'form' => $editForm->createView()
        ]);
    }

    private function getToolbarForm(InvoiceQuery $query, bool $simple): FormInterface
    {
        $form = $simple ? InvoiceToolbarSimpleForm::class : InvoiceToolbarForm::class;

        return $this->createForm($form, $query, [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
            'include_user' => $this->isGranted('view_other_timesheet'),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'invoice-print-form'
            ],
        ]);
    }

    private function getArchiveToolbarForm(InvoiceArchiveQuery $query): FormInterface
    {
        return $this->createForm(InvoiceArchiveForm::class, $query, [
            'action' => $this->generateUrl('admin_invoice_list', []),
            'method' => 'GET',
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'invoice-archive-form'
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

    private function createPaymentDateForm(Invoice $invoice, string $status): FormInterface
    {
        if (null === $invoice->getPaymentDate()) {
            $invoice->setPaymentDate($this->getDateTimeFactory()->createDateTime());
        }

        $url = $this->generateUrl('admin_invoice_status', ['id' => $invoice->getId(), 'status' => $status]);

        return $this->createForm(InvoicePaymentDateForm::class, $invoice, [
            'action' => $url,
            'method' => 'POST',
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
        ]);
    }
}
