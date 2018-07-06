<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\Toolbar\InvoiceToolbarForm;
use App\Model\InvoiceModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to print invoices.
 *
 * @Security("is_granted('ROLE_TEAMLEAD')")
 */
class InvoicePrintController extends AbstractController
{

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printInvoice(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/print.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printTimesheet(InvoiceModel $model)
    {
        return $this->render('invoice/renderer/timesheet.html.twig', [
            'model' => $model,
        ]);
    }

    /**
     * @Route("/invoice/freelancer", name="invoice_freelancer")
     *
     * @param InvoiceModel $model
     * @param Request $appRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printFreelancer(Request $appRequest, InvoiceModel $model)
    {
        $form = $this->createForm(InvoiceToolbarForm::class, $model->getQuery(), [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
        ]);

        $form->handleRequest($appRequest);

        return $this->render('invoice/renderer/freelancer.html.twig', [
            'form' => $form->createView(),
            'model' => $model,
        ]);
    }

    /**
     * @param InvoiceModel $model
     * @param Request $appRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printFreelancerPreview(Request $appRequest, InvoiceModel $model)
    {
        $form = $this->createForm(InvoiceToolbarForm::class, $model->getQuery(), [
            'action' => $this->generateUrl('invoice', []),
            'method' => 'GET',
        ]);

        $form->handleRequest($appRequest);

        return $this->render('invoice/renderer/preview.html.twig', [
            'form' => $form->createView(),
            'model' => $model,
        ]);
    }
}
