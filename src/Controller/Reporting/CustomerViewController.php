<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Customer\CustomerStatisticService;
use App\Reporting\CustomerView\CustomerViewForm;
use App\Reporting\CustomerView\CustomerViewQuery;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CustomerViewController extends AbstractController
{
    #[Route(path: '/reporting/customer_view', name: 'report_customer_view', methods: ['GET', 'POST'])]
    #[IsGranted('report:customer')]
    #[IsGranted(new Expression("is_granted('budget_any', 'customer')"))]
    public function __invoke(Request $request, CustomerStatisticService $service): Response
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new CustomerViewQuery($dateFactory->createDateTime(), $user);
        $form = $this->createFormForGetRequest(CustomerViewForm::class, $query);
        $form->submit($request->query->all(), false);

        $customers = $service->findCustomersForView($query);
        $entries = $service->getCustomerView($user, $customers, $query->getToday());

        $byCustomer = [];
        foreach ($entries as $entry) {
            $customer = $entry->getCustomer();
            $byCustomer[$customer->getId()] = $entry;
        }

        return $this->render('reporting/customer_view.html.twig', [
            'entries' => $byCustomer,
            'form' => $form->createView(),
            'report_title' => 'report_customer_view',
            'tableName' => 'customer_view_reporting',
            'now' => $dateFactory->createDateTime(),
        ]);
    }
}
