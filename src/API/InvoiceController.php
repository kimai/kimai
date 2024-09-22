<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Invoice;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceArchiveQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
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
     * Returns a paginated collection of invoices.
     *
     * Needs permission: view_invoice
     */
    #[IsGranted('view_invoice')]
    #[OA\Response(response: 200, description: 'Returns a collection of invoices', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/InvoiceCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_invoices')]
    #[Rest\QueryParam(name: 'begin', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records after this date will be included (format: HTML5 datetime-local, e.g. YYYY-MM-DDThh:mm:ss)')]
    #[Rest\QueryParam(name: 'end', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records before this date will be included (format: HTML5 datetime-local, e.g. YYYY-MM-DDThh:mm:ss)')]
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
            $query->addCustomer($customer);
        }

        $data = $this->repository->getPagerfantaForQuery($query);
        $view = $this->createPaginatedView($data);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one invoice.
     *
     * Needs permission: view_invoice
     */
    #[IsGranted('view_invoice')]
    #[OA\Response(response: 200, description: 'Returns one invoice', content: new OA\JsonContent(ref: '#/components/schemas/Invoice'))]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_invoice', requirements: ['id' => '\d+'])]
    public function getAction(Invoice $invoice): Response
    {
        $view = new View($invoice, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }
}
