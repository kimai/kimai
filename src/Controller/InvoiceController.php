<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Form\InvoiceTemplateForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Invoice\ServiceInvoice;
use App\Model\InvoiceModel;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceQuery;
use App\Repository\Query\TimesheetQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage invoices.
 *
 * @Route("/invoice")
 * @Security("is_granted('ROLE_TEAMLEAD')")
 */
class InvoiceController extends AbstractController
{

    /**
     * @var ServiceInvoice
     */
    protected $service;

    /**
     * InvoiceController constructor.
     * @param ServiceInvoice $service
     */
    public function __construct(ServiceInvoice $service)
    {
        $this->service = $service;
    }

    /**
     * @return InvoiceQuery
     * @throws \Exception
     */
    protected function getDefaultQuery()
    {
        $begin = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');

        $query = new InvoiceQuery();
        $query->setBegin($begin);
        $query->setEnd($end);
        $query->setUser($this->getUser());
        $query->setState(InvoiceQuery::STATE_STOPPED);

        return $query;
    }

    /**
     * @return \App\Repository\InvoiceTemplateRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(InvoiceTemplate::class);
    }

    /**
     * @Route("/", name="invoice")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexAction(Request $request)
    {
        if (!$this->getRepository()->hasTemplate()) {
            return $this->redirectToRoute('admin_invoice_template_create');
        }

        $entries = [];

        $query = $this->getDefaultQuery();
        $form = $this->getToolbarForm($query);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var InvoiceQuery $query */
            $query = $form->getData();
            $query->setResultType(TimesheetQuery::RESULT_TYPE_QUERYBUILDER);

            if ($query->getCustomer() !== null) {
                $query->getBegin()->setTime(0, 0, 0);
                $query->getEnd()->setTime(23, 59, 59);

                $queryBuilder = $this->getDoctrine()->getRepository(Timesheet::class)->findByQuery($query);
                $entries = $queryBuilder->getQuery()->getResult();
            }
        }

        $model = new InvoiceModel();
        $model->setQuery($query);
        $model->setEntries($entries);
        $model->setCustomer($query->getCustomer());

        $action = null;
        if ($query->getTemplate() !== null) {
            $generator = $this->service->getNumberGeneratorByName($query->getTemplate()->getNumberGenerator());
            if ($generator === null) {
                throw new \Exception('Unknown number generator: ' . $query->getTemplate()->getNumberGenerator());
            }

            $calculator = $this->service->getCalculatorByName($query->getTemplate()->getCalculator());
            if ($calculator === null) {
                throw new \Exception('Unknown invoice calculator: ' . $query->getTemplate()->getCalculator());
            }

            $model->setTemplate($query->getTemplate());
            $model->setCalculator($calculator);
            $model->setNumberGenerator($generator);
            $action = $this->service->getRendererActionByName($query->getTemplate()->getRenderer());
        }

        return $this->render('invoice/index.html.twig', [
            'model' => $model,
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/template", defaults={"page": 1}, name="admin_invoice_template")
     * @Route("/template/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_invoice_template_paginated")
     * @Method({"GET", "POST"})
     *
     * TODO permission
     *
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listTemplateAction($page, Request $request)
    {
        $templates = $this->getRepository()->findByQuery(new BaseQuery());

        return $this->render('invoice/templates.html.twig', [
            'entries' => $templates,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_invoice_template_edit")
     * @Method({"GET", "POST"})
     *
     * TODO permission
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editTemplateAction(InvoiceTemplate $template, Request $request)
    {
        return $this->renderTemplateForm($template, $request);
    }

    /**
     * @Route("/create", name="admin_invoice_template_create")
     * @Method({"GET", "POST"})
     *
     * TODO permission
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function createTemplateAction(Request $request)
    {
        if (!$this->getRepository()->hasTemplate()) {
            $this->flashWarning('invoice.first_template');
        }

        return $this->renderTemplateForm(new InvoiceTemplate(), $request);
    }

    /**
     * @param InvoiceTemplate $template
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function renderTemplateForm(InvoiceTemplate $template, Request $request)
    {
        $editForm = $this->createEditForm($template);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($template);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('admin_invoice_template');
        }

        return $this->render('invoice/template_edit.html.twig', [
            'template' => $template,
            'form' => $editForm->createView()
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function invoiceAction(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/print.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function timesheetAction(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/timesheet.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(InvoiceQuery $query)
    {
        return $this->createForm(InvoiceToolbarForm::class, $query, [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
        ]);
    }

    /**
     * @param InvoiceTemplate $template
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(InvoiceTemplate $template)
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
}
