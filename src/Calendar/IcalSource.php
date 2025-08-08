<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use Psr\Log\LoggerInterface;

final class IcalSource extends CalendarSource
{
    public function __construct(
        private IcsValidator $icsValidator,
        /** @phpstan-ignore property.onlyWritten */
        private SystemConfiguration $configuration,
        /** @phpstan-ignore property.onlyWritten */
        private User $user,
        string $id,
        string $uri,
        private LoggerInterface $logger,
        ?string $color = null,
        private string $prefix = ''
    ) {
        parent::__construct(CalendarSourceType::ICAL, $id, $uri, $color);
    }

    /**
     * @return array<array{id: string, title: string, start: string|null, end: string|null, allDay: bool, description?: string, location?: string}>
     */
    public function getEvents(): array
    {
        if (empty($this->getUri())) {
            $this->logger->warning('IcalSource: No URI configured for source', ['source_id' => $this->getId()]);
            return [];
        }

        try {
            $icsContent = $this->icsValidator->fetchAndValidateIcs($this->getUri());
            
            if ($icsContent === null) {
                $this->logger->error('IcalSource: Failed to fetch or validate ICS content', [
                    'source_id' => $this->getId(),
                    'uri' => $this->getUri()
                ]);
                return [];
            }

            $events = $this->icsValidator->parseIcsEvents($icsContent);
            
            $this->logger->info('IcalSource: Parsed events from ICS', [
                'source_id' => $this->getId(),
                'event_count' => count($events)
            ]);

            // Apply prefix to event titles
            $prefixedEvents = [];
            foreach ($events as $event) {
                if (!empty($this->prefix) && !empty($event['title'])) {
                    $event['title'] = $this->prefix . ' ' . $event['title'];
                }
                $prefixedEvents[] = $event;
            }

            return $prefixedEvents;

        } catch (\Exception $e) {
            $this->logger->error('IcalSource: Exception while getting events', [
                'source_id' => $this->getId(),
                'uri' => $this->getUri(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public static function createGlobalSource(IcsValidator $icsValidator, SystemConfiguration $configuration, User $user, LoggerInterface $logger): ?self
    {
        $globalIcalLink = $configuration->getCalendarGlobalIcalLink();
        
        if (empty($globalIcalLink)) {
            $logger->info('IcalSource: No global ICAL link configured');
            return null;
        }

        return new self(
            $icsValidator,
            $configuration,
            $user,
            'global_ical',
            $globalIcalLink,
            $logger,
            '#3788d8', // Default blue color
            ''
        );
    }

    public static function createUserSource(IcsValidator $icsValidator, SystemConfiguration $configuration, User $user, LoggerInterface $logger): ?self
    {
        $userIcalLink = $configuration->getCalendarUserIcalLink($user);
        
        if (empty($userIcalLink)) {
            $logger->info('IcalSource: No user ICAL link configured', [
                'user' => $user->getUserIdentifier()
            ]);
            return null;
        }

        return new self(
            $icsValidator,
            $configuration,
            $user,
            'user_ical',
            $userIcalLink,
            $logger,
            '#28a745', // Default green color
            ''
        );
    }
} 