<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Contract;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Event\WorkingTimeYearSummaryEvent;
use App\Form\ContractByUserForm;
use App\Reporting\YearByUser\YearByUser;
use App\Utils\PageSetup;
use App\WorkingTime\WorkingTimeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * For USER
 */
final class StatusController extends AbstractController
{
    #[Route(path: '/contract/status', name: 'user_contract_status', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, WorkingTimeService $workingTimeService, EventDispatcherInterface $eventDispatcher): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $defaultDate = $dateTimeFactory->createStartOfYear();

        $values = new YearByUser();
        $values->setUser($currentUser);
        $values->setDate($defaultDate);

        $form = $this->createFormForGetRequest(ContractByUserForm::class, $values, [
            'include_user' => false,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($values->getUser() === null) {
            $values->setUser($currentUser);
        }

        /** @var User $profile */
        $profile = $values->getUser();
        if ($values->getDate() === null) {
            $values->setDate(clone $defaultDate);
        }

        /** @var \DateTime $yearDate */
        $yearDate = $values->getDate();
        $year = $workingTimeService->getYear($profile, $yearDate);

        $event = new WorkingTimeYearSummaryEvent($profile, $year);
        $eventDispatcher->dispatch($event);

        $page = new PageSetup('status');
        $page->setHelp('contract-status.html');

        return $this->render('contract/status.html.twig', [
            'page_setup' => $page,
            'decimal' => false,
            'summaries' => $event->getSummaries(),
            'now' => $dateTimeFactory->createDateTime(),
            'year' => $year,
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }
}
