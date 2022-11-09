<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Customer\CustomerService;
use App\Entity\Customer;
use App\Entity\CustomerRate;
use App\Entity\User;
use App\Event\CustomerMetaDefinitionEvent;
use App\Form\API\CustomerApiEditForm;
use App\Form\API\CustomerRateApiForm;
use App\Repository\CustomerRateRepository;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerQuery;
use App\Utils\SearchTerm;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/customers')]
#[Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")]
#[OA\Tag(name: 'Customer')]
final class CustomerController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Customer', 'Customer_Entity'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Customer'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Customer'];
    public const GROUPS_RATE = ['Default', 'Entity', 'Customer_Rate'];

    public function __construct(
        private ViewHandlerInterface $viewHandler,
        private CustomerRepository $repository,
        private EventDispatcherInterface $dispatcher,
        private CustomerRateRepository $customerRateRepository
    ) {
    }

    /**
     * Returns a collection of customers
     */
    #[OA\Response(response: 200, description: 'Returns a collection of customer entities', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerCollection')))]
    #[Rest\Get(path: '', name: 'get_customers')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\QueryParam(name: 'visible', requirements: '\d+', strict: true, nullable: true, description: 'Visibility status to filter activities (1=visible, 2=hidden, 3=both)')]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: ASC)')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|name', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, name (default: name)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = new CustomerQuery();
        $query->setCurrentUser($user);

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        if (!empty($term = $paramFetcher->get('term'))) {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $data = $this->repository->getCustomersForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one customer
     */
    #[OA\Response(response: 200, description: 'Returns one customer entity', content: new OA\JsonContent(ref: '#/components/schemas/CustomerEntity'))]
    #[Rest\Get(path: '/{id}', name: 'get_customer', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getAction(Customer $id): Response
    {
        $view = new View($id, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new customer
     */
    #[OA\Post(description: 'Creates a new customer and returns it afterwards', responses: [new OA\Response(response: 200, description: 'Returns the new created customer', content: new OA\JsonContent(ref: '#/components/schemas/CustomerEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CustomerEditForm'))]
    #[Rest\Post(path: '', name: 'post_customer')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postAction(Request $request, CustomerService $customerService): Response
    {
        if (!$this->isGranted('create_customer')) {
            throw new AccessDeniedHttpException('User cannot create customers');
        }

        $customer = $customerService->createNewCustomer('');

        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(CustomerApiEditForm::class, $customer, [
            'include_budget' => $this->isGranted('budget', $customer),
            'include_time' => $this->isGranted('time', $customer),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveCustomer($customer);

            $view = new View($customer, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing customer
     */
    #[OA\Patch(description: 'Update an existing customer, you can pass all or just a subset of all attributes', responses: [new OA\Response(response: 200, description: 'Returns the updated customer', content: new OA\JsonContent(ref: '#/components/schemas/CustomerEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CustomerEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Customer ID to update', required: true)]
    #[Rest\Patch(path: '/{id}', name: 'patch_customer', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function patchAction(Request $request, int $id): Response
    {
        $customer = $this->repository->find($id);

        if (null === $customer) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $customer)) {
            throw new AccessDeniedHttpException('User cannot update customer');
        }

        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(CustomerApiEditForm::class, $customer, [
            'include_budget' => $this->isGranted('budget', $customer),
            'include_time' => $this->isGranted('time', $customer),
        ]);

        $form->setData($customer);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveCustomer($customer);

        $view = new View($customer, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing customer
     */
    #[OA\Response(response: 200, description: 'Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.', content: new OA\JsonContent(ref: '#/components/schemas/CustomerEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Customer record ID to set the meta-field value for', required: true)]
    #[Rest\Patch(path: '/{id}/meta', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\RequestParam(name: 'name', strict: true, nullable: false, description: 'The meta-field name')]
    #[Rest\RequestParam(name: 'value', strict: true, nullable: false, description: 'The meta-field value')]
    public function metaAction(int $id, ParamFetcherInterface $paramFetcher): Response
    {
        $customer = $this->repository->find($id);

        if (null === $customer) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $customer)) {
            throw new AccessDeniedHttpException('You are not allowed to update this customer');
        }

        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $customer->getMetaField($name))) {
            throw new \InvalidArgumentException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->repository->saveCustomer($customer);

        $view = new View($customer, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns a collection of all rates for one customer
     */
    #[OA\Response(response: 200, description: 'Returns a collection of customer rate entities', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerRate')))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The customer whose rates will be returned', required: true)]
    #[Rest\Get(path: '/{id}/rates', name: 'get_customer_rates', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getRatesAction(int $id): Response
    {
        /** @var Customer|null $customer */
        $customer = $this->repository->find($id);

        if (null === $customer) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $customer)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rates = $this->customerRateRepository->getRatesForCustomer($customer);

        $view = new View($rates, 200);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for a customer
     */
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Returns no content: 204 on successful delete')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The customer whose rate will be removed', required: true)]
    #[OA\Parameter(name: 'rateId', in: 'path', description: 'The rate to remove', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}/rates/{rateId}', name: 'delete_customer_rate', requirements: ['id' => '\d+', 'rateId' => '\d+'])]
    public function deleteRateAction(string $id, string $rateId): Response
    {
        /** @var Customer|null $customer */
        $customer = $this->repository->find($id);

        if (null === $customer) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $customer)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        /** @var CustomerRate|null $rate */
        $rate = $this->customerRateRepository->find($rateId);

        if (null === $rate || $rate->getCustomer() !== $customer) {
            throw new NotFoundException();
        }

        $this->customerRateRepository->deleteRate($rate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Adds a new rate to a customer
     */
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Returns the new created rate', content: new OA\JsonContent(ref: '#/components/schemas/CustomerRate'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The customer to add the rate for', required: true)]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CustomerRateForm'))]
    #[Rest\Post(path: '/{id}/rates', name: 'post_customer_rate', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function postRateAction(int $id, Request $request): Response
    {
        /** @var Customer|null $customer */
        $customer = $this->repository->find($id);

        if (null === $customer) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $customer)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rate = new CustomerRate();
        $rate->setCustomer($customer);

        $form = $this->createForm(CustomerRateApiForm::class, $rate, [
            'method' => 'POST',
        ]);

        $form->setData($rate);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_RATE);

            return $this->viewHandler->handle($view);
        }

        $this->customerRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }
}
