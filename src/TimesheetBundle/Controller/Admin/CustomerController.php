<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Controller\Admin;

use AppBundle\Controller\AbstractController;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TimesheetBundle\Entity\Customer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use TimesheetBundle\Form\CustomerEditForm;
use TimesheetBundle\Repository\CustomerRepository;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route("/admin/customer")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": 1}, name="admin_customer")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_customer_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page)
    {
        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(Customer::class)->findAll($page);

        return $this->render('TimesheetBundle:admin:customer.html.twig', ['entries' => $entries]);
    }

    /**
     * @Route("/{id}/edit", name="admin_customer_edit")
     * @Method({"GET", "POST"})
     *
     * @param Customer $customer
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Customer $customer, Request $request)
    {
        $editForm = $this->createEditForm($customer);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($customer);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute(
                'admin_customer', ['id' => $customer->getId()]
            );
        }

        return $this->render(
            'TimesheetBundle:admin:customer_edit.html.twig',
            [
                'customer' => $customer,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @param Customer $customer
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(Customer $customer)
    {
        return $this->createForm(
            CustomerEditForm::class,
            $customer,
            [
                'action' => $this->generateUrl('admin_customer_edit', ['id' => $customer->getId()]),
                'method' => 'POST'
            ]
        );
    }
}
