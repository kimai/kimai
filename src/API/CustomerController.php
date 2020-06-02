<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

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
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @RouteResource("Customer")
 * @SWG\Tag(name="Customer")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class CustomerController extends BaseApiController
{
    /**
     * @var CustomerRepository
     */
    private $repository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var CustomerRateRepository
     */
    private $customerRateRepository;

    public function __construct(ViewHandlerInterface $viewHandler, CustomerRepository $repository, EventDispatcherInterface $dispatcher, CustomerRateRepository $customerRateRepository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->customerRateRepository = $customerRateRepository;
    }

    /**
     * Returns a collection of customers
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of customer entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/CustomerCollection")
     *      )
     * )
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter activities (1=visible, 2=hidden, 3=both)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
     * @Rest\QueryParam(name="orderBy", requirements="id|name", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, name (default: name)")
     * @Rest\QueryParam(name="term", description="Free search term")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
        $view->getContext()->setGroups(['Default', 'Collection', 'Customer']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one customer
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one customer entity",
     *      @SWG\Schema(ref="#/definitions/CustomerEntity"),
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getAction(int $id): Response
    {
        $data = $this->repository->find($id);

        if (null === $data) {
            throw new NotFoundException();
        }

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new customer
     *
     * @SWG\Post(
     *      description="Creates a new customer and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created customer",
     *          @SWG\Schema(ref="#/definitions/CustomerEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/CustomerEditForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('create_customer')) {
            throw new AccessDeniedHttpException('User cannot create customers');
        }

        $customer = new Customer();

        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(CustomerApiEditForm::class, $customer, [
            'include_budget' => $this->isGranted('budget', $customer),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveCustomer($customer);

            $view = new View($customer, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing customer
     *
     * @SWG\Patch(
     *      description="Update an existing customer, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated customer",
     *          @SWG\Schema(ref="#/definitions/CustomerEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/CustomerEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Customer ID to update",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
        ]);

        $form->setData($customer);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveCustomer($customer);

        $view = new View($customer, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing customer
     *
     * @SWG\Response(
     *      response=200,
     *      description="Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.",
     *      @SWG\Schema(ref="#/definitions/CustomerEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Customer record ID to set the meta-field value for",
     *      required=true,
     * )
     * @Rest\RequestParam(name="name", strict=true, nullable=false, description="The meta-field name")
     * @Rest\RequestParam(name="value", strict=true, nullable=false, description="The meta-field value")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
        $view->getContext()->setGroups(['Default', 'Entity', 'Customer']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns a collection of all rates for one customer
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of customer rate entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/CustomerRate")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The customer whose rates will be returned",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
        $view->getContext()->setGroups(['Default', 'Entity', 'CustomerRate']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for an customer
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=204,
     *          description="Returns no content: 204 on successful delete"
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The customer whose rate will be removed",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="rateId",
     *      in="path",
     *      type="integer",
     *      description="The rate to remove",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Returns the new created rate",
     *      @SWG\Schema(ref="#/definitions/CustomerRate")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The customer to add the rate for",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/CustomerRateForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
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
            $view->getContext()->setGroups(['Default', 'Entity', 'CustomerRate']);

            return $this->viewHandler->handle($view);
        }

        $this->customerRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'CustomerRate']);

        return $this->viewHandler->handle($view);
    }
}
