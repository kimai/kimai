<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Customer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\CustomerEditForm;
use App\Form\Toolbar\CustomerToolbarForm;
use App\Repository\Query\CustomerQuery;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route("/admin/customer")
 * @Security("is_granted('ROLE_ADMIN')")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class CustomerController extends AbstractController
{

    /**
     * @return \App\Repository\CustomerRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(Customer::class);
    }

    /**
     * @Route("/", defaults={"page": 1}, name="admin_customer")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_customer_paginated")
     * @Method("GET")
     */
    public function indexAction($page, Request $request)
    {
        $query = new CustomerQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CustomerQuery $query */
            $query = $form->getData();
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('admin/customer.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/create", name="admin_customer_create")
     * @Method({"GET", "POST"})
     */
    public function createAction(Request $request)
    {
        return $this->renderCustomerForm(new Customer(), $request);
    }

    /**
     * @Route("/{id}/edit", name="admin_customer_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', customer)")
     */
    public function editAction(Customer $customer, Request $request)
    {
        return $this->renderCustomerForm($customer, $request);
    }

    /**
     * @param Customer $customer
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function renderCustomerForm(Customer $customer, Request $request)
    {
        $editForm = $this->createEditForm($customer);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($customer);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('admin_customer', ['id' => $customer->getId()]);
        }

        return $this->render('admin/customer_edit.html.twig', [
            'customer' => $customer,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * The route to delete an existing entry.
     *
     * @Route("/{id}/delete", name="admin_customer_delete")
     * @Method({"GET", "POST"})
     * @Security("is_granted('delete', customer)")
     *
     * @param Customer $customer
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Customer $customer, Request $request)
    {
        $stats = $this->getRepository()->getCustomerStatistics($customer);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_customer_delete', ['id' => $customer->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($stats->getRecordAmount() == 0 || ($deleteForm->isSubmitted() && $deleteForm->isValid())) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($customer);
            $entityManager->flush();

            $this->flashSuccess('action.deleted_successfully');

            return $this->redirectToRoute('admin_customer', ['id' => $customer->getId()]);
        }

        return $this->render('admin/customer_delete.html.twig', [
            'customer' => $customer,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @param CustomerQuery $query
     * @return \Symfony\Component\Form\FormInterface
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
     * @return \Symfony\Component\Form\FormInterface
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
            'method' => 'POST'
        ]);
    }
}
