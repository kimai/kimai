<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Entity\ICSCalendarSource;
use App\Entity\User;
use App\Repository\ICSCalendarSourceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ICSSource
{
    public function __construct(
        private ICSCalendarSourceRepository $repository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return CalendarSource[]
     */
    public function getSourcesForUser(User $user): array
    {
        $sources = [];
        $icsSources = $this->repository->findEnabledForUser($user);

        foreach ($icsSources as $icsSource) {
            $source = new CalendarSource(
                CalendarSourceType::ICAL,
                'ics_' . $icsSource->getId(),
                $this->generateICSUrl($icsSource),
                $icsSource->getColor() ?? '#3c8dbc'
            );
            
            // Add styling options to make external calendars stand out
            $source->addOption('borderColor', '#ff6b6b');
            $source->addOption('textColor', 'white');
            $source->addOption('display', 'block');
            $source->addOption('className', 'external-calendar-event');
            
            $sources[] = $source;
        }

        return $sources;
    }

    private function generateICSUrl(ICSCalendarSource $source): string
    {
        // This will be handled by an API endpoint that fetches and parses the ICS data
        return '/api/calendar/ics/' . $source->getId() . '/events';
    }

    /**
     * Fetch raw ICS data from the given URL
     */
    public function fetchRawICSData(ICSCalendarSource $source): ?string
    {
        try {
            $url = $source->getUrl();
            if ($url === null) {
                return null;
            }
            $response = $this->httpClient->request('GET', $url);
            return $response->getContent();
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch raw ICS calendar data', [
                'source_id' => $source->getId(),
                'url' => $source->getUrl(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch and parse ICS data from the given URL
     * @return array<int, array<string, mixed>>
     */
    public function fetchICSEvents(ICSCalendarSource $source, \DateTime $start, \DateTime $end): array
    {
        try {
            $icsData = $this->fetchRawICSData($source);
            if ($icsData === null) {
                return [];
            }

            $events = $this->parseICSData($icsData, $start, $end);

            // Update last_sync timestamp after successful fetch
            $source->setLastSync(new \DateTime());
            $this->repository->save($source, true);

            return $events;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch ICS calendar data', [
                'source_id' => $source->getId(),
                'url' => $source->getUrl(),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Parse ICS data and convert to calendar events
     * @return array<int, array<string, mixed>>
     */
    private function parseICSData(string $icsData, \DateTime $start, \DateTime $end): array
    {
        $events = [];
        $lines = explode("\n", $icsData);
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                if ($currentEvent !== null) {
                    $event = $this->createEventFromICSData($currentEvent, $start, $end);
                    if ($event !== null) {
                        $events[] = $event;
                    }
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null && strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $key = $parts[0];
                    $value = $parts[1];
                    
                    // Handle multi-line values
                    if (strpos($key, ';') !== false) {
                        $key = explode(';', $key)[0];
                    }
                    
                    $currentEvent[$key] = $value;
                }
            }
        }

        return $events;
    }

    /**
     * @param array<string, string> $icsEvent
     * @return array<string, mixed>|null
     */
    private function createEventFromICSData(array $icsEvent, \DateTime $start, \DateTime $end): ?array
    {
        $eventStart = $this->parseICSDateTime($icsEvent['DTSTART'] ?? null);
        $eventEnd = $this->parseICSDateTime($icsEvent['DTEND'] ?? null);
        
        if ($eventStart === null) {
            return null;
        }

        // Check if event is within the requested range
        if ($eventStart > $end || ($eventEnd !== null && $eventEnd < $start)) {
            return null;
        }

        $title = $this->unescapeICSValue($icsEvent['SUMMARY'] ?? 'Untitled Event');
        $description = $this->unescapeICSValue($icsEvent['DESCRIPTION'] ?? '');
        $location = $this->unescapeICSValue($icsEvent['LOCATION'] ?? '');

        $event = [
            'id' => $icsEvent['UID'] ?? uniqid('ics_', true),
            'title' => $title,
            'start' => $eventStart->format('c'),
            'allDay' => false,
        ];

        if ($eventEnd !== null) {
            $event['end'] = $eventEnd->format('c');
        }

        if (!empty($description)) {
            $event['description'] = $description;
        }

        if (!empty($location)) {
            $event['location'] = $location;
        }

        return $event;
    }

    private function parseICSDateTime(?string $icsDateTime): ?\DateTime
    {
        if ($icsDateTime === null) {
            return null;
        }

        // Remove timezone info for now and parse as local time
        $cleanDateTime = preg_replace('/[A-Z]{3}$/', '', $icsDateTime);
        
        // Handle different ICS date formats
        if ($cleanDateTime !== null && strlen($cleanDateTime) === 8) {
            // Date only (YYYYMMDD)
            $date = \DateTime::createFromFormat('Ymd', $cleanDateTime);
        } elseif ($cleanDateTime !== null && strlen($cleanDateTime) === 15) {
            // Date and time (YYYYMMDDTHHMMSS)
            $date = \DateTime::createFromFormat('Ymd\THis', $cleanDateTime);
        } elseif ($cleanDateTime !== null) {
            // Try to parse as is
            $date = \DateTime::createFromFormat('Ymd\THis', $cleanDateTime);
        } else {
            $date = false;
        }

        return $date ?: null;
    }

    private function unescapeICSValue(string $value): string
    {
        // Unescape ICS values
        $value = str_replace('\\n', "\n", $value);
        $value = str_replace('\\N', "\n", $value);
        $value = str_replace('\\t', "\t", $value);
        $value = str_replace('\\T', "\t", $value);
        $value = str_replace('\\,', ',', $value);
        $value = str_replace('\\;', ';', $value);
        $value = str_replace('\\\\', '\\', $value);
        
        return $value;
    }
} 