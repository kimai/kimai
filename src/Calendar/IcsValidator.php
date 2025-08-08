<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use Psr\Log\LoggerInterface;
use DateTime;
use DateTimeZone;
use Exception;

final class IcsValidator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Validates if the given content is a valid ICS/ICAL format
     */
    public function isValidIcs(string $content): bool
    {
        // Check for basic ICS structure
        if (empty($content)) {
            $this->logger->warning('IcsValidator: Empty content provided');
            return false;
        }

        // Check for BEGIN:VCALENDAR and END:VCALENDAR
        if (!str_contains($content, 'BEGIN:VCALENDAR') || !str_contains($content, 'END:VCALENDAR')) {
            $this->logger->warning('IcsValidator: Missing BEGIN:VCALENDAR or END:VCALENDAR');
            return false;
        }

        return true;
    }

    /**
     * Parses ICS content and returns an array of events
     * 
     * @return array<array{id: string, title: string, start: string|null, end: string|null, allDay: bool, description?: string, location?: string}>
     */
    public function parseIcsEvents(string $content): array
    {
        if (!$this->isValidIcs($content)) {
            $this->logger->error('IcsValidator: Cannot parse invalid ICS content');
            return [];
        }

        $events = [];
        $lines = explode("\n", $content);
        /** @var array<string, mixed>|null $currentEvent */
        $currentEvent = null;
        $inEvent = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Handle line folding
            if (preg_match('/^[ \t]/', $line) && $currentEvent !== null) {
                $line = substr($line, 1);
                $raw = $currentEvent['raw'] ?? '';
                if (is_string($raw)) {
                    $currentEvent['raw'] = $raw . $line;
                } else {
                    $currentEvent['raw'] = $line;
                }
                continue;
            }

            if (str_starts_with($line, 'BEGIN:VEVENT')) {
                $inEvent = true;
                $currentEvent = ['raw' => $line];
            } elseif (str_starts_with($line, 'END:VEVENT')) {
                if ($currentEvent !== null) {
                    $raw = $currentEvent['raw'] ?? '';
                    if (is_string($raw)) {
                        $currentEvent['raw'] = $raw . "\n" . $line;
                    } else {
                        $currentEvent['raw'] = $line;
                    }
                    if ($this->isValidEvent($currentEvent)) {
                        $formattedEvent = $this->formatEvent($currentEvent);
                        $events[] = $formattedEvent;
                    } else {
                        $this->logger->warning('IcsValidator: Skipping invalid event');
                    }
                }
                $inEvent = false;
                $currentEvent = null;
            } elseif ($inEvent && $currentEvent !== null) {
                $raw = $currentEvent['raw'] ?? '';
                if (is_string($raw)) {
                    $currentEvent['raw'] = $raw . "\n" . $line;
                } else {
                    $currentEvent['raw'] = $line;
                }
                $this->parseEventLine($line, $currentEvent);
            }
        }

        $this->logger->info('IcsValidator: Parsed {count} events from ICS content', ['count' => count($events)]);
        return $events;
    }

    /**
     * Validates if an event has the required fields
     * 
     * @param array<string, mixed> $event
     */
    private function isValidEvent(array $event): bool
    {
        // Check for required fields
        $requiredFields = ['uid', 'dtstart'];
        foreach ($requiredFields as $field) {
            if (empty($event[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parses a single line of an event
     * 
     * @param array<string, mixed> $event
     */
    private function parseEventLine(string $line, array &$event): void
    {
        if (preg_match('/^([A-Z-]+)(?:;(.+))?:(.+)$/', $line, $matches)) {
            $property = strtolower($matches[1]);
            $value = $this->unescapeValue($matches[3]);

            switch ($property) {
                case 'summary':
                    $event['summary'] = $value;
                    break;
                case 'description':
                    $event['description'] = $value;
                    break;
                case 'dtstart':
                    $event['dtstart'] = $value;
                    $event['start'] = $this->parseDateTime($value);
                    break;
                case 'dtend':
                    $event['dtend'] = $value;
                    $event['end'] = $this->parseDateTime($value);
                    break;
                case 'location':
                    $event['location'] = $value;
                    break;
                case 'uid':
                    $event['uid'] = $value;
                    break;
            }
        }
    }

    /**
     * Parses a datetime value from ICS format
     */
    private function parseDateTime(string $value): ?DateTime
    {
        // Handle different date formats
        $formats = [
            'Ymd\THis\Z', // UTC format
            'Ymd\THis',   // Local format
            'Ymd',        // Date only
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
        }

        $this->logger->warning('IcsValidator: Could not parse date', ['value' => $value]);
        return null;
    }

    /**
     * Unescapes ICS value (removes backslashes and newlines)
     */
    private function unescapeValue(string $value): string
    {
        // Unescape ICS values
        $value = str_replace('\\n', "\n", $value);
        $value = str_replace('\\t', "\t", $value);
        $value = str_replace('\\;', ';', $value);
        $value = str_replace('\\,', ',', $value);
        $value = str_replace('\\\\', '\\', $value);
        
        return $value;
    }

    /**
     * Formats an event for the calendar
     * 
     * @param array<string, mixed> $event
     * @return array{id: string, title: string, start: string|null, end: string|null, allDay: bool, description?: string, location?: string}
     */
    private function formatEvent(array $event): array
    {
        $formatted = [
            'id' => '',
            'title' => 'No Title',
            'start' => null,
            'end' => null,
            'allDay' => false,
        ];

        // Handle ID
        if (isset($event['uid']) && is_string($event['uid'])) {
            $formatted['id'] = $event['uid'];
        } else {
            $formatted['id'] = uniqid();
        }

        // Handle title
        if (isset($event['summary']) && is_string($event['summary'])) {
            $formatted['title'] = $event['summary'];
        }

        // Handle start date
        if (isset($event['start']) && $event['start'] instanceof \DateTime) {
            $formatted['start'] = $event['start']->format('Y-m-d\TH:i:s');
        }

        // Handle end date
        if (isset($event['end']) && $event['end'] instanceof \DateTime) {
            $formatted['end'] = $event['end']->format('Y-m-d\TH:i:s');
        }

        if (isset($event['description']) && is_string($event['description']) && !empty($event['description'])) {
            $formatted['description'] = $event['description'];
        }

        if (isset($event['location']) && is_string($event['location']) && !empty($event['location'])) {
            $formatted['location'] = $event['location'];
        }

        return $formatted;
    }

    /**
     * Fetches and validates ICS content from a URL
     */
    public function fetchAndValidateIcs(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Kimai/1.0',
                ]
            ]);

            $content = file_get_contents($url, false, $context);
            
            if ($content === false) {
                $this->logger->error('IcsValidator: Failed to fetch content from URL', ['url' => $url]);
                return null;
            }

            if ($this->isValidIcs($content)) {
                return $content;
            }

            $this->logger->error('IcsValidator: Fetched content is not valid ICS', ['url' => $url]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('IcsValidator: Exception while fetching ICS', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
} 