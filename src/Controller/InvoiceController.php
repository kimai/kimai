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
use App\Entity\MetaTableTypeInterface;
use App\Event\InvoiceDocumentsEvent;
use App\Event\InvoiceMetaDefinitionEvent;
use App\Event\InvoiceMetaDisplayEvent;
use App\Export\Spreadsheet\EntityWithMetaFieldsExporter;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Form\InvoiceDocumentUploadForm;
use App\Form\InvoiceEditForm;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceArchiveForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Type\DatePickerType;
use App\Form\Type\InvoiceTemplateType;
use App\Invoice\ServiceInvoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceArchiveQuery;
use App\Repository\Query\InvoiceQuery;
use App\Utils\DataTable;
use App\Utils\PageSetup;
use Exception;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

/**
 * Controller used to create invoices and manage invoice templates.
 */
#[Route(path: '/invoice')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('view_invoice')]
final class InvoiceController extends AbstractController
{
    public function __construct(
        private ServiceInvoice $service,
        private InvoiceTemplateRepository $templateRepository,
        private InvoiceRepository $invoiceRepository,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    #[Route(path: '/', name: 'invoice', methods: ['GET', 'POST'])]
    #[IsGranted('create_invoice')]
    public function indexAction(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            if ($this->isGranted('manage_invoice_template')) {
                return $this->redirectToRoute('admin_invoice_template_create');
            }
            $this->flashWarning('invoice.first_template');
        }

        $query = $this->getDefaultQuery();

        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request, ['invoiceDate'])) {
            return $this->redirectToRoute('invoice');
        }

        // this can be deleted in the future, but for now invalid bookmarks exists, which contain an old invoice date
        $query->setInvoiceDate($this->getDateTimeFactory()->createDateTime());

        $models = [];
        $total = 0;
        $searched = false;

        if ($form->isValid() && $query->getTemplate() !== null) {
            try {
                $models = $this->service->createModels($query);
                $searched = true;
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        }

        $forms = [];

        foreach ($models as $model) {
            $customer = $model->getCustomer();
            $customerTpl = $model->getTemplate();
            $total += \count($model->getCalculator()->getEntries());

            $values = [
                'invoiceDate' => null,
                'template' => $customerTpl
            ];

            $forms[] = $this->createFormWithName('customer_' . $customer->getId(), FormType::class, $values, [
                    'csrf_protection' => false,
                ])
                ->add('template', InvoiceTemplateType::class)
                ->add('invoiceDate', DatePickerType::class, [
                    'required' => true,
                    'label' => 'invoice_date',
                    'help' => 'invoice_date.help'
                ])
                ->createView();
        }

        return $this->render('invoice/index.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'models' => $models,
            'forms' => $forms,
            'form' => $form->createView(),
            'limit_preview' => ($total > 500),
            'searched' => $searched,
        ]);
    }

    #[Route(path: '/preview/{customer}/{token}', name: 'invoice_preview', methods: ['GET'])]
    #[IsGranted('create_invoice')]
    #[IsGranted('access', 'customer')]
    public function previewAction(Customer $customer, string $token, Request $request): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            return $this->redirectToRoute('invoice');
        }

        if (!$this->isCsrfTokenValid('invoice.preview', $token)) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('invoice');
        }

        // do not refresh token, preview is opening in new tabs and the listing page does not reload
        // so the new token would not be loaded

        $query = $this->getDefaultQuery();
        $query->setAllowTemplateOverwrite(false);
        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('invoice');
        }

        if ($form->isValid()) {
            try {
                $query->setCustomers([$customer]);
                $model = $this->service->createModel($query);

                return $this->service->renderInvoice($model, $this->dispatcher, true);
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        } else {
            $this->flashFormError($form);
        }

        return $this->redirectToRoute('invoice');
    }

    #[Route(path: '/save-invoice/{customer}/{token}', name: 'invoice_create', methods: ['GET'])]
    #[IsGranted('create_invoice')]
    #[IsGranted('access', 'customer')]
    public function createInvoiceAction(Customer $customer, string $token, Request $request, CustomerRepository $customerRepository): Response
    {
        if (!$this->templateRepository->hasTemplate()) {
            return $this->redirectToRoute('invoice');
        }

        if (!$this->isCsrfTokenValid('invoice.create', $token)) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('invoice');
        }

        $query = $this->getDefaultQuery();
        $query->setAllowTemplateOverwrite(false);
        $form = $this->getToolbarForm($query);
        if ($this->handleSearch($form, $request)) {
            return $this->redirectToRoute('invoice');
        }

        if ($form->isValid()) {
            try {
                $query->setCustomers([$customer]);
                $model = $this->service->createModel($query);

                // save default template for customer if not yet set
                if ($customer->getInvoiceTemplate() === null) {
                    $customer->setInvoiceTemplate($query->getTemplate());
                    $customerRepository->saveCustomer($customer);
                }

                $invoice = $this->service->createInvoice($model, $this->dispatcher);

                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_invoice_list', ['id' => $invoice->getId()]);
            } catch (Exception $ex) {
                $this->flashUpdateException($ex);
            }
        } else {
            $this->flashFormError($form);
        }

        return $this->redirectToRoute('invoice');
    }

    #[Route(path: '/change-status/{id}/{status}/{token}', name: 'admin_invoice_status', methods: ['GET', 'POST'])]
    #[IsGranted('create_invoice')]
    #[IsGranted(new Expression("is_granted('access', subject.getCustomer())"), 'invoice')]
    public function changeStatusAction(Invoice $invoice, string $status, string $token, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.status', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_list');
        }

        if ($status === Invoice::STATUS_PAID) {
            if (null === $invoice->getPaymentDate()) {
                $invoice->setPaymentDate($this->getDateTimeFactory()->createDateTime());
                $invoice->setIsPaid();
            }

            $form = $this->createInvoiceEditForm($invoice);
            $form->handleRequest($request);

            return $this->render('invoice/invoice_edit.html.twig', [
                'page_setup' => $this->createPageSetup(),
                'invoice' => $invoice,
                'form' => $form->createView()
            ]);
        }

        try {
            $this->service->changeInvoiceStatus($invoice, $status);
            $this->flashSuccess('action.update.success');
        } catch (Exception $ex) {
            $this->flashUpdateException($ex);
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    #[Route(path: '/edit/{id}', name: 'admin_invoice_edit', methods: ['GET', 'POST'])]
    #[IsGranted('create_invoice')]
    #[IsGranted(new Expression("is_granted('access', subject.getCustomer())"), 'invoice')]
    public function editAction(Invoice $invoice, Request $request): Response
    {
        $form = $this->createInvoiceEditForm($invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->invoiceRepository->saveInvoice($invoice);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_invoice_list');
            } catch (Exception $ex) {
                $this->handleFormUpdateException($ex, $form);
            }
        }

        return $this->render('invoice/invoice_edit.html.twig', [
            'page_setup' => $this->createPageSetup(),
            'invoice' => $invoice,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/delete/{id}/{token}', name: 'admin_invoice_delete', methods: ['GET'])]
    #[IsGranted('delete_invoice')]
    #[IsGranted(new Expression("is_granted('access', subject.getCustomer())"), 'invoice')]
    public function deleteInvoiceAction(Invoice $invoice, string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.status', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_list');
        }

        $csrfTokenManager->refreshToken('invoice.status');

        try {
            $this->service->deleteInvoice($invoice, $this->dispatcher);
            $this->flashSuccess('action.delete.success');
        } catch (Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_invoice_list');
    }

    #[Route(path: '/download/{id}', name: 'admin_invoice_download', methods: ['GET'])]
    #[IsGranted('view_invoice')]
    #[IsGranted(new Expression("is_granted('access', subject.getCustomer())"), 'invoice')]
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

    #[Route(path: '/show/{page}', defaults: ['page' => 1], requirements: ['page' => '[1-9]\d*'], name: 'admin_invoice_list', methods: ['GET'])]
    #[IsGranted('view_invoice')]
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

        $entries = $this->invoiceRepository->getPagerfantaForQuery($query);
        $metaColumns = $this->findMetaColumns($query);

        $table = new DataTable('invoices', $query);
        $table->setPagination($entries);
        $table->setSearchForm($form);
        $table->setPaginationRoute('admin_invoice_list');
        $table->setReloadEvents('kimai.invoiceUpdate');

        $table->addColumn('avatar', ['class' => 'text-nowrap w-avatar d-none d-md-table-cell', 'title' => false, 'orderBy' => false]);
        $table->addColumn('date', ['class' => 'd-none d-sm-table-cell text-nowrap w-min']);
        $table->addColumn('user', ['class' => 'd-none text-nowrap w-min', 'orderBy' => false]);
        $table->addColumn('customer', ['class' => 'alwaysVisible text-nowrap', 'orderBy' => false]);
        $table->addColumn('comment', ['class' => 'd-none', 'title' => 'description']);

        foreach ($metaColumns as $metaColumn) {
            $table->addColumn('mf_' . $metaColumn->getName(), ['title' => $metaColumn->getLabel(), 'class' => 'd-none', 'orderBy' => false]);
        }

        $table->addColumn('invoice_number', ['class' => 'd-none d-md-table-cell w-min', 'title' => 'invoice.number', 'orderBy' => 'invoice.number']);
        $table->addColumn('due_date', ['class' => 'd-none w-min', 'title' => 'invoice.due_days', 'orderBy' => false]);
        $table->addColumn('payment_date', ['class' => 'd-none w-min', 'title' => 'invoice.payment_date', 'orderBy' => false]);
        $table->addColumn('status', ['class' => 'd-none d-sm-table-cell w-min', 'orderBy' => 'status']);
        $table->addColumn('subtotal', ['class' => 'd-none text-end w-min', 'title' => 'invoice.subtotal', 'orderBy' => false]);
        $table->addColumn('tax', ['class' => 'd-none text-end w-min', 'title' => 'invoice.tax']);
        $table->addColumn('total_rate', ['class' => 'd-none d-md-table-cell text-end w-min']);
        $table->addColumn('actions', ['class' => 'actions']);

        $page = $this->createPageSetup('all_invoices');
        $page->setDataTable($table);
        $page->setActionName('invoice_archive');

        return $this->render('invoice/listing.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
            'download' => $invoice,
            'metaColumns' => $metaColumns,
        ]);
    }

    #[Route(path: '/export', name: 'invoice_export', methods: ['GET'])]
    #[IsGranted('view_invoice')]
    public function exportAction(Request $request, EntityWithMetaFieldsExporter $exporter)
    {
        $query = new InvoiceArchiveQuery();
        $query->setCurrentUser($this->getUser());

        $form = $this->getArchiveToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        $entries = $this->invoiceRepository->getInvoicesForQuery($query);

        $spreadsheet = $exporter->export(
            Invoice::class,
            $entries,
            new InvoiceMetaDisplayEvent($query, InvoiceMetaDisplayEvent::INVOICE)
        );
        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-invoices');

        return $writer->getFileResponse($spreadsheet);
    }

    #[Route(path: '/template/{page}', requirements: ['page' => '[1-9]\d*'], defaults: ['page' => 1], name: 'admin_invoice_template', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function listTemplateAction(int $page): Response
    {
        $query = new BaseQuery();
        $query->setPage($page);

        $entries = $this->templateRepository->getPagerfantaForQuery($query);

        $table = new DataTable('invoice_template', $query);
        $table->setPagination($entries);
        $table->setPaginationRoute('admin_invoice_template');
        $table->setReloadEvents('kimai.invoiceTemplateUpdate');

        $table->addColumn('name', ['class' => 'alwaysVisible', 'orderBy' => false]);
        $table->addColumn('title', ['class' => 'd-none text-nowrap', 'orderBy' => false]);
        $table->addColumn('company', ['class' => 'd-none', 'orderBy' => false]);
        $table->addColumn('vat_id', ['class' => 'd-none text-nowrap', 'orderBy' => false]);
        $table->addColumn('tax_rate', ['class' => 'd-none text-nowrap', 'orderBy' => false]);
        $table->addColumn('due_days', ['class' => 'd-none text-nowrap', 'orderBy' => false]);
        $table->addColumn('address', ['class' => 'd-none', 'orderBy' => false]);
        $table->addColumn('contact', ['class' => 'd-none', 'orderBy' => false]);
        $table->addColumn('calculator', ['class' => 'd-none', 'orderBy' => false, 'title' => 'invoice_calculator', 'translation_domain' => 'invoice-calculator']);
        $table->addColumn('renderer', ['class' => 'd-none', 'orderBy' => false, 'title' => 'invoice_renderer', 'translation_domain' => 'invoice-renderer']);
        $table->addColumn('language', ['class' => 'd-none text-nowrap', 'orderBy' => false]);
        $table->addColumn('actions', ['class' => 'actions', 'orderBy' => false]);

        $page = $this->createPageSetup('admin_invoice_template.title');
        $page->setDataTable($table);
        $page->setActionName('invoice_templates');

        return $this->render('invoice/templates.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
        ]);
    }

    #[Route(path: '/template/{id}/edit', name: 'admin_invoice_template_edit', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function editTemplateAction(InvoiceTemplate $template, Request $request): Response
    {
        return $this->renderTemplateForm($template, $request);
    }

    #[Route(path: '/document_download/{document}', name: 'admin_invoice_document_download', methods: ['GET'])]
    #[IsGranted('upload_invoice_template')]
    public function downloadDocument(string $document, Environment $twig): Response
    {
        $event = new InvoiceDocumentsEvent($this->service->getDocuments(true));
        $this->dispatcher->dispatch($event);

        foreach ($event->getInvoiceDocuments() as $doc) {
            if ($document === $doc->getId()) {
                return $this->file($doc->getFilename());
            }
        }

        throw $this->createNotFoundException('Unknown document: ' . $document);
    }

    #[Route(path: '/document_reload/{document}', name: 'admin_invoice_document_reload', methods: ['GET', 'POST'])]
    #[IsGranted('upload_invoice_template')]
    public function reloadDocument(string $document, Environment $twig): Response
    {
        $event = new InvoiceDocumentsEvent($this->service->getDocuments(true));
        $this->dispatcher->dispatch($event);

        $reloaded = false;

        foreach ($event->getInvoiceDocuments() as $doc) {
            if ($document === $doc->getId() && $doc->isTwig()) {
                $reloaded = true;
                try {
                    $twig->enableAutoReload();
                    $twig->load('@invoice/' . basename($doc->getFilename()));
                    $twig->disableAutoReload();
                    $this->flashSuccess('Reloaded template');
                } catch (Exception $ex) {
                    $this->flashException($ex, 'Failed to reload template: ' . $ex->getMessage());
                }
            }
        }

        if (!$reloaded) {
            throw $this->createNotFoundException('Unknown document: ' . $document);
        }

        return $this->redirectToRoute('admin_invoice_document_upload');
    }

    #[Route(path: '/document_upload', name: 'admin_invoice_document_upload', methods: ['GET', 'POST'])]
    #[IsGranted('upload_invoice_template')]
    public function uploadDocumentAction(Request $request, string $projectDirectory, InvoiceDocumentRepository $documentRepository, Environment $twig, SystemConfiguration $systemConfiguration): Response
    {
        $dir = $documentRepository->getUploadDirectory();
        $invoiceDir = $dir;

        // do not execute realpath, as it will return an empty string if the invoice directory is NOT existing!
        if ($invoiceDir[0] !== '/') {
            $invoiceDir = $projectDirectory . DIRECTORY_SEPARATOR . $dir;
        }
        $invoiceDir = rtrim($invoiceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $used = [];
        foreach ($this->templateRepository->findAll() as $template) {
            $used[$template->getRenderer()] = $template;
        }

        $event = new InvoiceDocumentsEvent($this->service->getDocuments(true));
        $this->dispatcher->dispatch($event);

        $documents = [];
        foreach ($event->getInvoiceDocuments() as $document) {
            $isUsed = \array_key_exists($document->getId(), $used);
            $template = null;
            if ($isUsed) {
                $template = $used[$document->getId()];
            }
            $documents[] = [
                'document' => $document,
                'template' => $template,
                'used' => $isUsed,
            ];
        }

        $canUpload = true;
        $uploadError = null;

        if (\count($documents) >= $event->getMaximumAllowedDocuments()) {
            $uploadError = 'invoice_document.max_reached';
            $canUpload = false;
        }

        if (!file_exists($invoiceDir)) {
            @mkdir($invoiceDir, 0o777);
        }

        if (!is_dir($invoiceDir)) {
            $uploadError = 'error.directory_missing';
            $canUpload = false;
        } elseif (!is_writable($invoiceDir)) {
            $uploadError = 'error.directory_protected';
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

                $originalName = $uploadedFile->getClientOriginalName();
                $safeFilename = null;
                $extension = null;
                $success = true;

                $allowed = InvoiceDocumentUploadForm::EXTENSIONS_NO_TWIG;
                if ((bool) $systemConfiguration->find('invoice.upload_twig') === true) {
                    $allowed = InvoiceDocumentUploadForm::EXTENSIONS;
                }

                foreach ($allowed as $ext) {
                    $len = \strlen($ext);
                    if (substr_compare($originalName, $ext, -$len) === 0) {
                        $extension = $ext;
                        $withoutExtension = str_replace($ext, '', $originalName);
                        $safeFilename = transliterator_transliterate(InvoiceDocumentUploadForm::FILENAME_RULE, $withoutExtension);
                        break;
                    }
                }

                if ($safeFilename === null || $extension === null) {
                    $success = false;
                    $this->flashError('Invalid file given');
                } else {
                    $newFilename = substr($safeFilename, 0, 20) . $extension;

                    try {
                        $uploadedFile->move($invoiceDir, $newFilename);

                        // if this is a twig file, we directly try to compile the template
                        if (stripos($newFilename, '.twig') !== false) {
                            try {
                                $twig->enableAutoReload();
                                $twig->load('@invoice/' . $newFilename);
                                $twig->disableAutoReload();
                            } catch (Exception $ex) {
                                unlink($invoiceDir . $newFilename);
                                $success = false;
                                $this->flashException($ex, 'File was deleted, as Twig template is broken: ' . $ex->getMessage());
                            }
                        }
                    } catch (Exception $ex) {
                        $this->flashException($ex, 'action.upload.error');
                    }
                }

                if ($success) {
                    $this->flashSuccess('action.update.success');

                    return $this->redirectToRoute('admin_invoice_document_upload');
                }
            }
        }

        $page = $this->createPageSetup('admin_invoice_template.title');

        return $this->render('invoice/document_upload.html.twig', [
            'page_setup' => $page,
            'error_replacer' => ['%max%' => $event->getMaximumAllowedDocuments(), '%dir%' => $dir],
            'upload_error' => $uploadError,
            'can_upload' => $canUpload,
            'form' => $form->createView(),
            'documents' => $documents,
            'baseDirectory' => $projectDirectory . DIRECTORY_SEPARATOR,
        ]);
    }

    #[Route(path: '/document/{id}/delete/{token}', name: 'invoice_document_delete', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function deleteDocument(string $id, string $token, CsrfTokenManagerInterface $csrfTokenManager, InvoiceDocumentRepository $documentRepository): Response
    {
        $document = $documentRepository->findByName($id);
        if ($document === null) {
            throw $this->createNotFoundException();
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.delete_document', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_document_upload');
        }

        $csrfTokenManager->refreshToken('invoice.delete_document');

        foreach ($documentRepository->findBuiltIn() as $doc) {
            if ($doc->getId() === $id) {
                $this->flashError('Document is built-in and cannot be deleted.');

                return $this->redirectToRoute('admin_invoice_document_upload');
            }
        }

        foreach ($this->templateRepository->findAll() as $template) {
            if ($template->getRenderer() === $id) {
                $this->flashError('Document is used and cannot be deleted.');

                return $this->redirectToRoute('admin_invoice_document_upload');
            }
        }

        try {
            $documentRepository->remove($document);
            $this->flashSuccess('action.delete.success');
        } catch (Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_invoice_document_upload');
    }

    #[Route(path: '/template/create/{id}', name: 'admin_invoice_template_copy', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function copyTemplateAction(Request $request, InvoiceTemplate $copyFrom): Response
    {
        return $this->createTemplate($request, $copyFrom);
    }

    #[Route(path: '/template/create', name: 'admin_invoice_template_create', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function createTemplateAction(Request $request): Response
    {
        return $this->createTemplate($request, null);
    }

    private function createTemplate(Request $request, ?InvoiceTemplate $copyFrom = null): Response
    {
        $template = new InvoiceTemplate();
        $template->setLanguage($request->getLocale());

        if (null !== $copyFrom) {
            $template = clone $copyFrom;
            $template->setName($copyFrom->getName() . ' (1)');
        }

        return $this->renderTemplateForm($template, $request);
    }

    #[Route(path: '/template/{id}/delete/{csrfToken}', name: 'admin_invoice_template_delete', methods: ['GET', 'POST'])]
    #[IsGranted('manage_invoice_template')]
    public function deleteTemplate(InvoiceTemplate $template, string $csrfToken, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('invoice.delete_template', $csrfToken))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('admin_invoice_template');
        }

        $csrfTokenManager->refreshToken('invoice.delete_template');

        try {
            $this->templateRepository->removeTemplate($template);
            $this->flashSuccess('action.delete.success');
        } catch (Exception $ex) {
            $this->flashDeleteException($ex);
        }

        return $this->redirectToRoute('admin_invoice_template');
    }

    private function getDefaultQuery(): InvoiceQuery
    {
        $factory = $this->getDateTimeFactory();
        $begin = $factory->getStartOfMonth();
        $end = $factory->getEndOfMonth();

        $query = new InvoiceQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setInvoiceDate($factory->createDateTime());
        // limit access to data from teams
        $query->setCurrentUser($this->getUser());

        if (!$this->isGranted('view_other_timesheet')) {
            // limit access to own data
            $query->setUser($this->getUser());
        }

        return $query;
    }

    private function flashFormError(FormInterface $form): void
    {
        $err = '';
        foreach ($form->getErrors(true, true) as $error) {
            $err .= PHP_EOL . '[' . $error->getOrigin()->getName() . '] ' . $error->getMessage();
        }

        $this->flashError('action.update.error', $err);
    }

    private function renderTemplateForm(InvoiceTemplate $template, Request $request): Response
    {
        $editForm = $this->createTemplateEditForm($template);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->templateRepository->saveTemplate($template);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_invoice_template');
            } catch (Exception $ex) {
                $this->handleFormUpdateException($ex, $editForm);
            }
        }

        $page = $this->createPageSetup('admin_invoice_template.title');

        return $this->render('invoice/template_edit.html.twig', [
            'page_setup' => $page,
            'template' => $template,
            'form' => $editForm->createView()
        ]);
    }

    private function getToolbarForm(InvoiceQuery $query): FormInterface
    {
        return $this->createSearchForm(InvoiceToolbarForm::class, $query, [
            'action' => $this->generateUrl('invoice', []),
            'include_user' => $this->isGranted('view_other_timesheet'),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'invoice-print-form'
            ],
        ]);
    }

    private function getArchiveToolbarForm(InvoiceArchiveQuery $query): FormInterface
    {
        return $this->createSearchForm(InvoiceArchiveForm::class, $query, [
            'action' => $this->generateUrl('admin_invoice_list', []),
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'attr' => [
                'id' => 'invoice-archive-form'
            ],
        ]);
    }

    private function createTemplateEditForm(InvoiceTemplate $template): FormInterface
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

    /**
     * @param InvoiceArchiveQuery $query
     * @return MetaTableTypeInterface[]
     */
    private function findMetaColumns(InvoiceArchiveQuery $query): array
    {
        $event = new InvoiceMetaDisplayEvent($query, InvoiceMetaDisplayEvent::INVOICE);
        $this->dispatcher->dispatch($event);

        return $event->getFields();
    }

    private function createInvoiceEditForm(Invoice $invoice): FormInterface
    {
        $event = new InvoiceMetaDefinitionEvent($invoice);
        $this->dispatcher->dispatch($event);

        return $this->createForm(InvoiceEditForm::class, $invoice, [
            'action' => $this->generateUrl('admin_invoice_edit', ['id' => $invoice->getId()]),
            'method' => 'POST',
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
        ]);
    }

    private function createPageSetup(string $title = 'invoices'): PageSetup
    {
        $page = new PageSetup($title);
        $page->setHelp('invoices.html');

        return $page;
    }
}
