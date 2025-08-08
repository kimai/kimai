<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\IcalSource;
use App\Calendar\IcsValidator;
use App\Configuration\SystemConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;

/**
 * Controller for serving ICAL events as JSON
 */
#[Route(path: '/ical')]
final class IcalController extends AbstractController
{
    public function __construct(
        private IcsValidator $icsValidator,
        private SystemConfiguration $configuration,
        private LoggerInterface $logger
    ) {
    }

    #[Route(path: '/events/{type}', name: 'ical_events', methods: ['GET'])]
    public function getEvents(string $type, Request $request): JsonResponse
    {
        $this->logger->info('IcalController: Processing request for events', [
            'type' => $type,
            'user' => $this->getUser()?->getUserIdentifier() ?? 'anonymous'
        ]);

        if (!$this->getUser()) {
            $this->logger->warning('IcalController: Unauthenticated access attempt');
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $user = $this->getUser();
        
        if (!$user instanceof User) {
            $this->logger->error('IcalController: User is not of expected type');
            return new JsonResponse(['error' => 'Invalid user type'], 400);
        }
        
        try {
            $source = null;
            
            if ($type === 'global') {
                $source = IcalSource::createGlobalSource($this->icsValidator, $this->configuration, $user, $this->logger);
            } elseif ($type === 'user') {
                $source = IcalSource::createUserSource($this->icsValidator, $this->configuration, $user, $this->logger);
            } else {
                $this->logger->warning('IcalController: Invalid event type requested', ['type' => $type]);
                return new JsonResponse(['error' => 'Invalid event type'], 400);
            }

            if ($source === null) {
                $this->logger->info('IcalController: No ICAL source available for type', ['type' => $type]);
                return new JsonResponse([]);
            }

            $events = $source->getEvents();
            
            $this->logger->info('IcalController: Retrieved events from source', [
                'type' => $type,
                'event_count' => count($events),
                'source_id' => $source->getId()
            ]);

            // Format events for FullCalendar
            $formattedEvents = [];
            foreach ($events as $event) {
                $formattedEvent = [
                    'id' => $event['id'] ?? uniqid(),
                    'title' => $event['title'] ?? 'No Title',
                    'start' => $event['start'] ?? null,
                    'end' => $event['end'] ?? null,
                    'allDay' => $event['allDay'] ?? false,
                    'backgroundColor' => $source->getColor() ?? '#3788d8',
                    'borderColor' => $source->getColor() ?? '#3788d8',
                    'textColor' => $this->getContrastColor($source->getColor() ?? '#3788d8'),
                ];

                if (!empty($event['description'])) {
                    $formattedEvent['description'] = $event['description'];
                }

                if (!empty($event['location'])) {
                    $formattedEvent['location'] = $event['location'];
                }

                $formattedEvents[] = $formattedEvent;
            }

            return new JsonResponse($formattedEvents);

        } catch (\Exception $e) {
            $this->logger->error('IcalController: Exception while processing events', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Get contrast color for text based on background color
     */
    private function getContrastColor(string $hexColor): string
    {
        // Remove # if present
        $hexColor = ltrim($hexColor, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Return black or white based on luminance
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
} 