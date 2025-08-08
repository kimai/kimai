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
        $this->logger->debug('IcsValidator: Validating ICS content', ['content_length' => strlen($content)]);
        
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

        $this->logger->debug('IcsValidator: ICS content appears valid');
        return true;
    }

    /**
     * Parses ICS content and returns an array of events
     * 
     * @return array<array{
     *   summary: string,
     *   description: ?string,
     *   start: DateTime,
     *   end: DateTime,
     *   location: ?string,
     *   uid: string
     * }>
     */
    public function parseIcsEvents(string $content): array
    {
        $this->logger->debug('IcsValidator: Starting to parse ICS events');
        
        if (!$this->isValidIcs($content)) {
            $this->logger->error('IcsValidator: Cannot parse invalid ICS content');
            return [];
        }

        $events = [];
        $lines = explode("\n", $content);
        $currentEvent = null;
        $inEvent = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Handle line folding
            if (preg_match('/^[ \t]/', $line) && $currentEvent !== null) {
                $line = substr($line, 1);
                $currentEvent['raw'] .= $line;
                continue;
            }

            if (str_starts_with($line, 'BEGIN:VEVENT')) {
                $inEvent = true;
                $currentEvent = ['raw' => $line];
                $this->logger->debug('IcsValidator: Found BEGIN:VEVENT');
            } elseif (str_starts_with($line, 'END:VEVENT')) {
                if ($currentEvent !== null) {
                    $currentEvent['raw'] .= "\n" . $line;
                    if ($this->isValidEvent($currentEvent)) {
                        $formattedEvent = $this->formatEvent($currentEvent);
                        $events[] = $formattedEvent;
                        $this->logger->debug('IcsValidator: Added valid event', ['summary' => $formattedEvent['title'] ?? 'No title']);
                    } else {
                        $this->logger->warning('IcsValidator: Skipping invalid event');
                    }
                }
                $inEvent = false;
                $currentEvent = null;
            } elseif ($inEvent && $currentEvent !== null) {
                $currentEvent['raw'] .= "\n" . $line;
                $this->parseEventLine($line, $currentEvent);
            }
        }

        $this->logger->info('IcsValidator: Parsed {count} events from ICS content', ['count' => count($events)]);
        return $events;
    }

    /**
     * Validates if an event has the required fields
     */
    private function isValidEvent(array $event): bool
    {
        // Check for required fields
        $requiredFields = ['uid', 'dtstart'];
        foreach ($requiredFields as $field) {
            if (empty($event[$field])) {
                $this->logger->debug('IcsValidator: Event missing required field', ['field' => $field]);
                return false;
            }
        }

        return true;
    }

    /**
     * Parses a single line of an event
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
     */
    private function formatEvent(array $event): array
    {
        $formatted = [
            'id' => $event['uid'] ?? uniqid(),
            'title' => $event['summary'] ?? 'No Title',
            'start' => $event['start']?->format('Y-m-d\TH:i:s') ?? null,
            'end' => $event['end']?->format('Y-m-d\TH:i:s') ?? null,
            'allDay' => false,
        ];

        if (!empty($event['description'])) {
            $formatted['description'] = $event['description'];
        }

        if (!empty($event['location'])) {
            $formatted['location'] = $event['location'];
        }

        return $formatted;
    }

    /**
     * Fetches and validates ICS content from a URL
     */
    public function fetchAndValidateIcs(string $url): ?string
    {
        $this->logger->debug('IcsValidator: Fetching ICS from URL', ['url' => $url]);
        
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

            $this->logger->debug('IcsValidator: Successfully fetched content', [
                'url' => $url,
                'content_length' => strlen($content)
            ]);

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