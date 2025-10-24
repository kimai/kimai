<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotionService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly ?string $notionApiKey = null,
        private readonly ?string $notionProjectDatabaseId = null,
        private readonly ?string $notionTimeEntryDatabaseId = null,
        private readonly ?string $notionTaskDatabaseId = null
    ) {
    }

    /**
     * Fetch projects from Notion database with caching
     * 
     * @return array<string, string> Array of project_name => project_id (for Symfony ChoiceType)
     */
    public function getProjects(): array
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey) || empty($this->notionProjectDatabaseId)) {
            $this->logger->debug('Notion API not configured, skipping project fetch');
            return [];
        }

        // Cache the results for 5 minutes to avoid API calls on every page load
        return $this->cache->get('notion_projects', function (ItemInterface $item) {
            $item->expiresAfter(300); // 5 minutes cache
            
            return $this->fetchProjectsFromNotion();
        });
    }

    /**
     * Fetch projects directly from Notion API
     * 
     * @return array<string, string> Array of project_name => project_id
     */
    private function fetchProjectsFromNotion(): array
    {

        try {
            $response = $this->httpClient->request('POST', 
                sprintf('https://api.notion.com/v1/databases/%s/query', $this->notionProjectDatabaseId), 
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => '{}',  // Send empty JSON object, not array
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();
            $projects = [];

            foreach ($data['results'] ?? [] as $page) {
                $pageId = $page['id'] ?? null;
                
                // Extract project name from title property
                $projectName = $this->extractProjectName($page);
                
                if ($pageId && $projectName) {
                    // Format: name => id (for Symfony ChoiceType display)
                    $projects[$projectName] = $pageId;
                }
            }

            // Sort alphabetically by project name (keys)
            ksort($projects, SORT_NATURAL | SORT_FLAG_CASE);

            $this->logger->info('getProjects', [
                'notion_projects_fetched' => count($projects)
            ]);

            return $projects;
        } catch (\Throwable $e) {
            // Get detailed error information for debugging
            $errorDetails = [
                'error' => $e->getMessage(),
                'notion_fetch_failed' => true,
                'database_id' => $this->notionProjectDatabaseId,
            ];
            
            // Try to get response body if it's an HTTP exception
            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    $errorDetails['status_code'] = $response->getStatusCode();
                    $errorDetails['response_body'] = $response->getContent(false);
                } catch (\Throwable $responseError) {
                    // Ignore errors getting response details
                }
            }
            
            $this->logger->error('getProjects', $errorDetails);
            
            return [];
        }
    }

    /**
     * Extract project name from Notion page properties
     */
    private function extractProjectName(array $page): ?string
    {
        $properties = $page['properties'] ?? [];
        
        // Try to find a "Name" or "Title" property
        foreach ($properties as $propertyName => $property) {
            if (in_array($propertyName, ['Name', 'Title', 'Project', 'name', 'title', 'project'], true)) {
                $type = $property['type'] ?? null;
                
                if ($type === 'title' && !empty($property['title'])) {
                    return $this->extractTextContent($property['title']);
                }
                
                if ($type === 'rich_text' && !empty($property['rich_text'])) {
                    return $this->extractTextContent($property['rich_text']);
                }
            }
        }

        // Fallback: use the first title property found
        foreach ($properties as $property) {
            if (($property['type'] ?? null) === 'title' && !empty($property['title'])) {
                return $this->extractTextContent($property['title']);
            }
        }

        return null;
    }

    /**
     * Extract plain text from Notion rich text array
     */
    private function extractTextContent(array $textArray): ?string
    {
        $text = '';
        foreach ($textArray as $textObj) {
            $text .= $textObj['plain_text'] ?? '';
        }
        
        return !empty($text) ? $text : null;
    }

    /**
     * Create a time entry page in Notion
     * 
     * @param array $properties Properties for the time entry page
     * @return string|null The created page ID or null on failure
     */
    public function createTimeEntry(array $properties): ?string
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey) || empty($this->notionTimeEntryDatabaseId)) {
            $this->logger->debug('createTimeEntry', [
                'notion_api_not_configured' => true,
                'has_api_key' => !empty($this->notionApiKey),
                'has_database_id' => !empty($this->notionTimeEntryDatabaseId)
            ]);
            return null;
        }

        try {
            $payload = [
                'parent' => [
                    'type' => 'database_id',
                    'database_id' => $this->notionTimeEntryDatabaseId,
                ],
                'properties' => $properties,
            ];

            $this->logger->info('createTimeEntry', [
                'step' => 'sending_request',
                'database_id' => $this->notionTimeEntryDatabaseId,
                'property_count' => count($properties),
                'properties' => array_keys($properties),
                'full_payload' => $payload
            ]);

            $response = $this->httpClient->request(
                'POST',
                'https://api.notion.com/v1/pages',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();
            $pageId = $data['id'] ?? null;

            $this->logger->info('createTimeEntry', [
                'step' => 'success',
                'notion_page_created' => true,
                'page_id' => $pageId
            ]);

            return $pageId;
        } catch (\Throwable $e) {
            $errorDetails = [
                'step' => 'error',
                'error' => $e->getMessage(),
                'notion_create_failed' => true,
                'database_id' => $this->notionTimeEntryDatabaseId,
            ];
            
            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    $errorDetails['status_code'] = $response->getStatusCode();
                    $errorDetails['response_body'] = $response->getContent(false);
                } catch (\Throwable $responseError) {
                    // Ignore errors getting response details
                }
            }
            
            $this->logger->error('createTimeEntry', $errorDetails);
            
            return null;
        }
    }

    /**
     * Get the schema of the time entry database to see what properties exist
     * 
     * @return array|null Database schema or null on failure
     */
    public function getTimeEntryDatabaseSchema(): ?array
    {
        if (empty($this->notionApiKey) || empty($this->notionTimeEntryDatabaseId)) {
            return null;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('https://api.notion.com/v1/databases/%s', $this->notionTimeEntryDatabaseId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                    ],
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();
            return $data['properties'] ?? [];
        } catch (\Throwable $e) {
            $this->logger->error('getTimeEntryDatabaseSchema', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find a task by its ID number in the Notion task database
     * 
     * @param int $taskId The numeric task ID (e.g., 1914 from "md-1914")
     * @return string|null The Notion page ID of the task, or null if not found
     */
    public function findTaskById(int $taskId): ?string
    {
        if (empty($this->notionApiKey) || empty($this->notionTaskDatabaseId)) {
            return null;
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('https://api.notion.com/v1/databases/%s/query', $this->notionTaskDatabaseId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'filter' => [
                            'property' => 'ID',
                            'unique_id' => [
                                'equals' => $taskId
                            ]
                        ]
                    ],
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();
            
            if (!empty($data['results'][0]['id'])) {
                $taskPageId = $data['results'][0]['id'];
                
                $this->logger->info('findTaskById', [
                    'task_id' => $taskId,
                    'found' => true,
                    'page_id' => $taskPageId
                ]);
                
                return $taskPageId;
            }

            $this->logger->debug('findTaskById', [
                'task_id' => $taskId,
                'found' => false
            ]);
            
            return null;
        } catch (\Throwable $e) {
            $this->logger->error('findTaskById', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Build Notion properties for a timesheet entry
     * 
     * @param array $timesheetData The timesheet data from WebhookService
     * @return array Properties formatted for Notion API
     */
    public function buildTimesheetProperties(array $timesheetData): array
    {
        $this->logger->info('buildTimesheetProperties', [
            'step' => 'start',
            'has_description' => !empty($timesheetData['description']),
            'has_begin' => !empty($timesheetData['begin']),
            'has_end' => !empty($timesheetData['end']),
            'has_activity' => !empty($timesheetData['activity']['name']),
            'has_user' => !empty($timesheetData['user']),
            'has_project_meta' => !empty($timesheetData['meta_fields']['notion_project_id']),
            'has_tags' => !empty($timesheetData['tags']),
            'tags' => $timesheetData['tags'] ?? [],
            'timesheet_id' => $timesheetData['id'] ?? null
        ]);

        $properties = [];

        // Add title - use description or default text
        $title = $timesheetData['description'] ?? 'Time Entry';
        if (empty($title)) {
            $title = 'Time Entry';
        }
        
        $properties['Name'] = [
            'title' => [
                [
                    'text' => [
                        'content' => $title
                    ]
                ]
            ]
        ];
        
        $this->logger->info('buildTimesheetProperties', [
            'step' => 'set_name',
            'title' => $title
        ]);

        // Add Duration as a date field with start and end times
        if (!empty($timesheetData['begin'])) {
            $properties['Duration'] = [
                'date' => [
                    'start' => $timesheetData['begin'],
                    'end' => $timesheetData['end'] ?? null,
                ]
            ];
            
            $this->logger->info('buildTimesheetProperties', [
                'step' => 'set_duration',
                'start' => $timesheetData['begin'],
                'end' => $timesheetData['end'] ?? null
            ]);
        }

        // Add Type (Activity name)
        if (!empty($timesheetData['activity']['name'])) {
            $properties['Type'] = [
                'select' => [
                    'name' => $timesheetData['activity']['name']
                ]
            ];
            
            $this->logger->info('buildTimesheetProperties', [
                'step' => 'set_type',
                'activity_name' => $timesheetData['activity']['name']
            ]);
        }

        // Add Person (User display name, preferring alias/title over username)
        $personName = $timesheetData['user']['alias'] 
            ?? $timesheetData['user']['title'] 
            ?? $timesheetData['user']['username'] 
            ?? null;
        
        if ($personName) {
            $properties['Person'] = [
                'select' => [
                    'name' => $personName
                ]
            ];
            
            $this->logger->info('buildTimesheetProperties', [
                'step' => 'set_person',
                'person_name' => $personName,
                'used_alias' => !empty($timesheetData['user']['alias']),
                'used_title' => empty($timesheetData['user']['alias']) && !empty($timesheetData['user']['title']),
                'used_username' => empty($timesheetData['user']['alias']) && empty($timesheetData['user']['title'])
            ]);
        }

        // Add Notion Project ID if available (using the emoji property name)
        if (!empty($timesheetData['meta_fields']['notion_project_id'])) {
            $properties['🏗️ Projects & Epics'] = [
                'relation' => [
                    [
                        'id' => $timesheetData['meta_fields']['notion_project_id']
                    ]
                ]
            ];
            
            $this->logger->info('buildTimesheetProperties', [
                'step' => 'set_project',
                'project_id' => $timesheetData['meta_fields']['notion_project_id']
            ]);
        }

        // Add Time entry id (Kimai timesheet ID)
        if (!empty($timesheetData['id'])) {
            $properties['Time entry id'] = [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => (string) $timesheetData['id']
                        ]
                    ]
                ]
            ];
            
            $this->logger->info('buildTimesheetProperties', [
                'step' => 'set_time_entry_id',
                'kimai_id' => $timesheetData['id']
            ]);
        }

        // Look for task ID in tags (pattern: md-1234 or md-12345)
        if (!empty($timesheetData['tags']) && is_array($timesheetData['tags'])) {
            foreach ($timesheetData['tags'] as $tag) {
                // Match pattern like "md-1914" or "MD-1914: Task Title" (case insensitive)
                // The pattern matches MD-#### at the start, optionally followed by anything
                if (preg_match('/^[Mm][Dd]-(\d{4,5})(?::|$)/i', $tag, $matches)) {
                    $taskId = (int) $matches[1];
                    
                    // Query Notion to find the task
                    $taskPageId = $this->findTaskById($taskId);
                    
                    if ($taskPageId) {
                        $properties['📦 Tasks'] = [
                            'relation' => [
                                [
                                    'id' => $taskPageId
                                ]
                            ]
                        ];
                        
                        $this->logger->info('buildTimesheetProperties', [
                            'linked_task' => true,
                            'task_tag' => $tag,
                            'task_id' => $taskId,
                            'task_page_id' => $taskPageId
                        ]);
                        
                        // Only link to the first matching task
                        break;
                    } else {
                        $this->logger->warning('buildTimesheetProperties', [
                            'task_not_found' => true,
                            'task_tag' => $tag,
                            'task_id' => $taskId
                        ]);
                    }
                }
            }
        }

        $this->logger->info('buildTimesheetProperties', [
            'step' => 'complete',
            'property_count' => count($properties),
            'properties' => array_keys($properties)
        ]);

        return $properties;
    }

    /**
     * Update a time entry page in Notion
     * 
     * @param string $pageId The Notion page ID to update
     * @param array $properties Properties to update on the time entry page
     * @return bool True if successful, false otherwise
     */
    public function updateTimeEntry(string $pageId, array $properties): bool
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey)) {
            $this->logger->debug('updateTimeEntry', [
                'notion_api_not_configured' => true
            ]);
            return false;
        }

        try {
            $payload = [
                'properties' => $properties,
            ];

            $this->logger->info('updateTimeEntry', [
                'step' => 'sending_request',
                'page_id' => $pageId,
                'property_count' => count($properties),
                'properties' => array_keys($properties)
            ]);

            $response = $this->httpClient->request(
                'PATCH',
                sprintf('https://api.notion.com/v1/pages/%s', $pageId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();

            $this->logger->info('updateTimeEntry', [
                'step' => 'success',
                'page_id' => $pageId
            ]);

            return true;
        } catch (\Throwable $e) {
            $errorDetails = [
                'step' => 'error',
                'error' => $e->getMessage(),
                'notion_update_failed' => true,
                'page_id' => $pageId,
            ];
            
            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    $errorDetails['status_code'] = $response->getStatusCode();
                    $errorDetails['response_body'] = $response->getContent(false);
                } catch (\Throwable $responseError) {
                    // Ignore errors getting response details
                }
            }
            
            $this->logger->error('updateTimeEntry', $errorDetails);
            
            return false;
        }
    }

    /**
     * Delete (archive) a time entry page in Notion
     * 
     * @param string $pageId The Notion page ID to archive
     * @return bool True if successful, false otherwise
     */
    public function deleteTimeEntry(string $pageId): bool
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey)) {
            $this->logger->debug('deleteTimeEntry', [
                'notion_api_not_configured' => true
            ]);
            return false;
        }

        try {
            $this->logger->info('deleteTimeEntry', [
                'step' => 'sending_request',
                'page_id' => $pageId
            ]);

            // In Notion API, we archive pages rather than delete them
            $response = $this->httpClient->request(
                'PATCH',
                sprintf('https://api.notion.com/v1/pages/%s', $pageId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->notionApiKey,
                        'Notion-Version' => '2022-06-28',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'archived' => true
                    ],
                    'timeout' => 10,
                ]
            );

            $data = $response->toArray();

            $this->logger->info('deleteTimeEntry', [
                'step' => 'success',
                'page_id' => $pageId,
                'archived' => true
            ]);

            return true;
        } catch (\Throwable $e) {
            $errorDetails = [
                'step' => 'error',
                'error' => $e->getMessage(),
                'notion_delete_failed' => true,
                'page_id' => $pageId,
            ];
            
            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    $errorDetails['status_code'] = $response->getStatusCode();
                    $errorDetails['response_body'] = $response->getContent(false);
                } catch (\Throwable $responseError) {
                    // Ignore errors getting response details
                }
            }
            
            $this->logger->error('deleteTimeEntry', $errorDetails);
            
            return false;
        }
    }
}

