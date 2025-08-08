<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Calendar\ICSSource;
use App\Entity\ICSCalendarSource;
use App\Repository\ICSCalendarSourceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/calendar/ics')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class ICSCalendarController extends BaseApiController
{
    public function __construct(
        private ICSSource $icsSource,
        private ICSCalendarSourceRepository $repository
    ) {
    }

    #[Route(path: '/{id}/events', name: 'api_calendar_ics_events', methods: ['GET'])]
    public function getEventsAction(ICSCalendarSource $source, Request $request): Response
    {
        $user = $this->getUser();
        
        if ($source->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only access your own ICS calendar sources.');
        }

        // Fetch the raw ICS data and return it directly
        $icsData = $this->icsSource->fetchRawICSData($source);

        if ($icsData === null) {
            return new Response('No data available', 404, ['Content-Type' => 'text/plain']);
        }

        // Update last_sync timestamp after successful fetch
        $source->setLastSync(new \DateTime());
        $this->repository->save($source, true);

        return new Response($icsData, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="calendar.ics"'
        ]);
    }
} 