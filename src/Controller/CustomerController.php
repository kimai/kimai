<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\FormConfiguration;
use App\Entity\Customer;
use App\Event\CustomerMetaDefinitionEvent;
use App\Form\CustomerEditForm;
use App\Form\CustomerTeamPermissionForm;
use App\Form\Toolbar\CustomerToolbarForm;
use App\Form\Type\CustomerType;
use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\CustomerQuery;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage customer in the admin part of the site.
 *
 * @Route(path="/admin/customer")
 * @Security("is_granted('view_customer')")
 */
class CustomerController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    private $repository;
    /**
     * @var FormConfiguration
     */
    private $configuration;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param CustomerRepository $repository
     * @param FormConfiguration $configuration
     */
    public function __construct(CustomerRepository $repository, FormConfiguration $configuration, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \App\Repository\CustomerRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_customer", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_customer_paginated", methods={"GET"})
     * @Security("is_granted('view_customer')")
     *
     * @param int $page
     * @param Request $request
     * @return Response
     */
    public function indexAction($page, Request $request)
    {
        $query = new CustomerQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        $entries = $this->getRepository()->getPagerfantaForQuery($query);

        return $this->render('customer/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'showFilter' => $query->isDirty(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/create", name="admin_customer_create", methods={"GET", "POST"})
     * @Security("is_granted('create_customer')")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->configuration->getCustomerDefaultTimezone()) {
            $timezone = $this->configuration->getCustomerDefaultTimezone();
        }

        $customer = new Customer();
        $customer->setCountry($this->configuration->getCustomerDefaultCountry());
        $customer->setCurrency($this->configuration->getCustomerDefaultCurrency());
        $customer->setTimezone($timezone);

        return $this->renderCustomerForm($customer, $request);
    }

    /**
     * @Route(path="/{id}/permissions", name="admin_customer_permissions", methods={"GET", "POST"})
     * @Security("is_granted('edit', customer)")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function teamPermissions(Customer $customer, Request $request)
    {
        $form = $this->createForm(CustomerTeamPermissionForm::class, $customer, [
            'action' => $this->generateUrl('admin_customer_permissions', ['id' => $customer->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                dump($customer->getTeams());
                exit;
                $this->getRepository()->saveCustomer($customer);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_customer');
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('customer/permissions.html.twig', [
            'customer' => $customer,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/{id}/budget", name="admin_customer_budget", methods={"GET"})
     * @Security("is_granted('budget', customer)")
     *
     * @param Customer $customer
     * @return Response
     */
    public function budgetAction(Customer $customer)
    {
        return $this->render('customer/budget.html.twig', [
            'customer' => $customer,
            'stats' => $this->getRepository()->getCustomerStatistics($customer)
        ]);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_customer_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', customer)")
     *
     * @param Customer $customer
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editAction(Customer $customer, Request $request)
    {
        return $this->renderCustomerForm($customer, $request);
    }

    /**
     * @Route(path="/{id}/delete", name="admin_customer_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', customer)")
     *
     * @param Customer $customer
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function deleteAction(Customer $customer, Request $request)
    {
        $stats = $this->getRepository()->getCustomerStatistics($customer);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.customerUpdate kimai.customerDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('customer', CustomerType::class, [
                'label' => 'label.customer',
                'query_builder' => function (CustomerRepository $repo) use ($customer) {
                    $query = new CustomerFormTypeQuery();
                    $query->setCustomerToIgnore($customer);

                    return $repo->getQueryBuilderForFormType($query);
                },
                'required' => false,
            ])
            ->setAction($this->generateUrl('admin_customer_delete', ['id' => $customer->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                $this->getRepository()->deleteCustomer($customer, $deleteForm->get('customer')->getData());
                $this->flashSuccess('action.delete.success');
            } catch (ORMException $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('admin_customer');
        }

        return $this->render('customer/delete.html.twig', [
            'customer' => $customer,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @param Customer $customer
     * @param Request $request
     * @return RedirectResponse|Response
     */
    protected function renderCustomerForm(Customer $customer, Request $request)
    {
        $event = new CustomerMetaDefinitionEvent($customer);
        $this->dispatcher->dispatch(CustomerMetaDefinitionEvent::class, $event);

        $editForm = $this->createEditForm($customer);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->getRepository()->saveCustomer($customer);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_customer');
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @param CustomerQuery $query
     * @return FormInterface
     */
    protected function getToolbarForm(CustomerQuery $query)
    {
        return $this->createForm(CustomerToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_customer', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    /**
     * @param Customer $customer
     * @return FormInterface
     */
    private function createEditForm(Customer $customer)
    {
        if ($customer->getId() === null) {
            $url = $this->generateUrl('admin_customer_create');
        } else {
            $url = $this->generateUrl('admin_customer_edit', ['id' => $customer->getId()]);
        }

        return $this->createForm(CustomerEditForm::class, $customer, [
            'action' => $url,
            'method' => 'POST',
            'include_budget' => $this->isGranted('budget', $customer)
        ]);
    }
}
