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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TimesheetBundle\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
class CustomerController extends Controller
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
     */
    public function editAction($id, Request $request)
    {
        $entity = $this->getById($id);
        $editForm = $this->createEditForm($entity);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute(
                'admin_customer', ['id' => $entity->getId()]
            );
        }

        return $this->render(
            'TimesheetBundle:admin:customer_edit.html.twig',
            [
                'customer' => $entity,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @param $id
     * @return null|Customer
     */
    protected function getById($id)
    {
        /* @var $repo CustomerRepository */
        $repo = $this->getDoctrine()->getRepository(Customer::class);
        $activity = $repo->getById($id);
        if (null === $activity) {
            throw new NotFoundHttpException('Customer "'.$id.'" does not exist');
        }
        return $activity;
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
