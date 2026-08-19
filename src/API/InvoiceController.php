<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Invoice;
use App\Entity\InvoiceMeta;
use App\Entity\InvoiceTemplate;
use App\Invoice\InvoiceService;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceDocumentRepository;
use App\Repository\InvoiceRepository;
use App\Repository\InvoiceTemplateRepository;
use App\Repository\Query\InvoiceArchiveQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints;

#[Route(path: '/invoices')]
#[IsGranted('API')]
#[OA\Tag(name: 'Invoice')]
final class InvoiceController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Invoice', 'Invoice_Entity'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Invoice'];

    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly InvoiceRepository $repository,
    ) {
    }

    /**
     * Fetch invoices
     */
    #[IsGranted('view_invoice')]
    #[OA\Response(response: 200, description: 'Returns a collection of invoices', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/InvoiceCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_invoices')]
    #[Rest\QueryParam(name: 'begin', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only invoices created at or after this date-time will be included (format: HTML5 datetime-local, e.g. YYYY-MM-DDThh:mm:ss)')]
    #[Rest\QueryParam(name: 'end', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only invoices created before or at this date-time will be included (format: HTML5 datetime-local, e.g. YYYY-MM-DDThh:mm:ss)')]
    #[Rest\QueryParam(name: 'customers', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of customer IDs to filter, e.g.: customers[]=1&customers[]=2')]
    #[Rest\QueryParam(name: 'status', map: true, requirements: 'pending|paid|canceled|new', strict: true, nullable: true, default: [], description: 'Invoice status: pending, paid, canceled, new. Default: all')]
    #[Rest\QueryParam(name: 'page', requirements: '\d+', strict: true, nullable: true, description: 'The page to display, renders a 404 if not found (default: 1)')]
    #[Rest\QueryParam(name: 'size', requirements: '\d+', strict: true, nullable: true, description: 'The amount of entries for each page (default: 50)')]
    public function cgetAction(ParamFetcherInterface $paramFetcher, CustomerRepository $customerRepository): Response
    {
        $query = new InvoiceArchiveQuery();
        $this->prepareQuery($query, $paramFetcher);
        $factory = $this->getDateTimeFactory();

        $begin = $paramFetcher->get('begin');
        if (\is_string($begin) && $begin !== '') {
            $query->setBegin($factory->createDateTime($begin));
        }

        $end = $paramFetcher->get('end');
        if (\is_string($end) && $end !== '') {
            $query->setEnd($factory->createDateTime($end));
        }

        /** @var array<string> $status */
        $status = $paramFetcher->get('status');
        if (\is_array($status)) {
            foreach ($status as $s) {
                $query->addStatus($s);
            }
        }

        /** @var array<int> $customers */
        $customers = $paramFetcher->get('customers');
        foreach ($customerRepository->findByIds(array_unique($customers)) as $customer) {
            if (!$this->isGranted('access', $customer)) {
                throw $this->createAccessDeniedException('Cannot access Customer: ' . $customer->getId());
            }
            $query->addCustomer($customer);
        }

        $data = $this->repository->getPagerfantaForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Fetch invoice
     */
    #[IsGranted('view_invoice', 'invoice')]
    #[OA\Response(response: 200, description: 'Returns one invoice', content: new OA\JsonContent(ref: '#/components/schemas/Invoice'))]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_invoice', requirements: ['id' => '\d+'])]
    public function getAction(Invoice $invoice): Response
    {
        $view = new View($invoice, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update invoice custom-fields
     */
    #[IsGranted('edit_invoice', 'invoice')]
    #[OA\Response(response: 200, description: 'Sets the value of configured custom-fields. You cannot create unknown custom-fields.', content: new OA\JsonContent(ref: '#/components/schemas/Invoice'))]
    #[OA\Parameter(name: 'id', description: 'Invoice ID to set the custom-fields for', in: 'path', required: true)]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(type: 'array', items: new OA\Items(new Model(type: InvoiceMeta::class))))]
    #[Route(path: '/{id}/custom-fields', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateMetaFields(Invoice $invoice, Request $request, InvoiceService $invoiceService): Response
    {
        $invoiceService->loadMetaFields($invoice);
        $dirty = false;

        foreach ($request->request->all() as $preference) {
            // why is this not handled by FosRestBundle ?
            if (!\is_array($preference)) {
                throw new BadRequestHttpException('Invalid request, array expected');
            }

            if (!\array_key_exists('name', $preference) || !\array_key_exists('value', $preference)) {
                throw new BadRequestHttpException('Missing required parameter "name" or "value"');
            }

            $name = $preference['name'];
            $value = $preference['value'];

            if (null === ($meta = $invoice->getMetaField($name))) {
                throw $this->createNotFoundException(\sprintf('Unknown custom-field "%s" requested', $name));
            }

            $meta->setValue($value);
            $dirty = true;
        }

        if ($dirty) {
            $invoiceService->saveInvoice($invoice);
        }

        $view = new View($invoice, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Download invoice
     */
    #[IsGranted('view_invoice', 'invoice')]
    #[OA\Response(
        response: 200,
        description: 'Downloads the invoice document as an attachment. The content type depends on the configured invoice renderer.',
        headers: [
            new OA\Header(header: 'Content-Disposition', description: 'Attachment filename', schema: new OA\Schema(type: 'string')),
        ],
        content: [
            new OA\MediaType(mediaType: 'application/pdf', schema: new OA\Schema(type: 'string', format: 'binary')),
            new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', schema: new OA\Schema(type: 'string', format: 'binary')),
            new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary')),
            new OA\MediaType(mediaType: 'application/vnd.oasis.opendocument.spreadsheet', schema: new OA\Schema(type: 'string', format: 'binary')),
            new OA\MediaType(mediaType: 'text/html', schema: new OA\Schema(type: 'string')),
            new OA\MediaType(mediaType: 'application/xml', schema: new OA\Schema(type: 'string')),
            new OA\MediaType(mediaType: 'text/xml', schema: new OA\Schema(type: 'string')),
            new OA\MediaType(mediaType: 'application/octet-stream', schema: new OA\Schema(type: 'string', format: 'binary')),
        ]
    )]
    #[Route(path: '/{id}/download', name: 'download_invoice', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadAction(Invoice $invoice, InvoiceService $service): Response
    {
        $file = $service->getInvoiceFile($invoice);

        if (null === $file) {
            throw $this->createNotFoundException(
                \sprintf('Invoice file could not be found for invoice ID "%s"', $invoice->getId())
            );
        }

        return $this->file($file->getRealPath(), $file->getBasename());
    }

    /**
     * Delete invoice
     */
    #[IsGranted('delete_invoice', 'invoice')]
    #[OA\Delete(description: 'Deletes the invoice and its generated document.', responses: [new OA\Response(response: 204, description: 'Empty')])]
    #[OA\Parameter(name: 'id', description: 'Invoice ID to delete', in: 'path', required: true)]
    #[Route(path: '/{id}', name: 'delete_invoice', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteInvoice(Invoice $invoice, InvoiceService $service): Response
    {
        $service->deleteInvoice($invoice);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete an invoice document
     *
     * Invoice documents are the files used to render an invoice, they cannot be created
     * through the API. This endpoint is used by the invoice document screen.
     */
    #[IsGranted('manage_invoice_template')]
    #[OA\Delete(description: 'Deletes an uploaded invoice document. Built-in documents and documents which are used by a template cannot be deleted.', responses: [new OA\Response(response: 204, description: 'Empty')], x: ['internal' => true])]
    #[OA\Parameter(name: 'id', description: 'Invoice document ID to delete', in: 'path', required: true)]
    #[Route(path: '/documents/{id}', name: 'delete_invoice_document', methods: ['DELETE'])]
    public function deleteDocument(string $id, InvoiceDocumentRepository $documentRepository, InvoiceTemplateRepository $templateRepository): Response
    {
        $document = $documentRepository->findByName($id);
        if ($document === null) {
            throw $this->createNotFoundException('Unknown invoice document: ' . $id);
        }

        foreach ($documentRepository->findBuiltIn() as $doc) {
            if ($doc->getId() === $id) {
                throw new BadRequestHttpException('Document is built-in and cannot be deleted.');
            }
        }

        foreach ($templateRepository->findAll() as $template) {
            if ($template->getRenderer() === $id) {
                throw new BadRequestHttpException('Document is used and cannot be deleted.');
            }
        }

        $documentRepository->remove($document);

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }

    /**
     * Delete an invoice template
     */
    #[IsGranted('manage_invoice_template')]
    #[OA\Delete(description: 'Deletes an invoice template.', responses: [new OA\Response(response: 204, description: 'Empty')], x: ['internal' => true])]
    #[OA\Parameter(name: 'id', description: 'Invoice template ID to delete', in: 'path', required: true)]
    #[Route(path: '/templates/{id}', name: 'delete_invoice_template', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteTemplate(InvoiceTemplate $template, InvoiceTemplateRepository $templateRepository): Response
    {
        $templateRepository->removeTemplate($template);

        return $this->viewHandler->handle(new View(null, Response::HTTP_NO_CONTENT));
    }
}
