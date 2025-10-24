<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\EventSubscriber;

use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetDeletePreEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetUpdatePostEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Process;

class NotionTimesheetSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly ?string $notionApiKey = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Use low priority (-200) to run after webhook subscriber (-100)
            // This ensures the timesheet is fully saved before syncing to Notion
            TimesheetCreatePostEvent::class => ['onTimesheetCreated', -200],
            TimesheetUpdatePostEvent::class => ['onTimesheetUpdated', -200],
            TimesheetStopPostEvent::class => ['onTimesheetStopped', -200],
            TimesheetDeletePreEvent::class => ['onTimesheetDeleted', -200],
        ];
    }

    public function onTimesheetCreated(TimesheetCreatePostEvent $event): void
    {
        $this->logger->info('onTimesheetCreated', [
            'timesheet_id' => $event->getTimesheet()->getId(),
            'has_project' => $event->getTimesheet()->getProject() !== null
        ]);
        $this->dispatchNotionSync($event->getTimesheet());
    }

    public function onTimesheetUpdated(TimesheetUpdatePostEvent $event): void
    {
        $timesheet = $event->getTimesheet();
        
        // Query the database directly to get the notion_time_entry_id
        // During update events, invisible metadata fields are filtered out by Kimai
        $notionPageId = $this->getNotionTimeEntryId($timesheet->getId());
        
        if ($notionPageId) {
            // Update the existing Notion entry asynchronously
            $this->dispatchNotionUpdate($timesheet, $notionPageId);
        } else {
            // If no Notion entry exists yet, create one asynchronously
            $this->dispatchNotionSync($timesheet);
        }
    }
    
    /**
     * Get the Notion time entry ID directly from the database
     * This is needed because invisible meta fields are filtered out during update events
     */
    private function getNotionTimeEntryId(int $timesheetId): ?string
    {
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT value FROM kimai2_timesheet_meta 
                WHERE timesheet_id = :id AND name = 'notion_time_entry_id' 
                LIMIT 1";
        
        try {
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery(['id' => $timesheetId]);
            $row = $result->fetchAssociative();
            
            return $row ? $row['value'] : null;
        } catch (\Throwable $e) {
            $this->logger->error('getNotionTimeEntryId', [
                'timesheet_id' => $timesheetId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function onTimesheetDeleted(TimesheetDeletePreEvent $event): void
    {
        $timesheet = $event->getTimesheet();
        
        // Query the database directly to get the notion_time_entry_id
        $notionPageId = $this->getNotionTimeEntryId($timesheet->getId());
        
        if ($notionPageId) {
            // Delete (archive) the Notion entry asynchronously
            $this->dispatchNotionDelete($timesheet, $notionPageId);
        }
    }

    public function onTimesheetStopped(TimesheetStopPostEvent $event): void
    {
        $timesheet = $event->getTimesheet();
        
        // Check if a Notion entry already exists
        $notionPageId = $timesheet->getMetaField('notion_time_entry_id')?->getValue();
        
        if (!$notionPageId) {
            // Create Notion entry when timesheet is stopped (asynchronously)
            $this->dispatchNotionSync($timesheet);
        }
    }

    /**
     * Spawn a background process to sync the timesheet to Notion
     */
    private function dispatchNotionSync($timesheet): void
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey)) {
            $this->logger->debug('dispatchNotionSync', [
                'timesheet_id' => $timesheet->getId(),
                'skip_reason' => 'notion_not_configured'
            ]);
            return;
        }

        try {
            // Check if the project has a Notion project linked
            $project = $timesheet->getProject();
            if ($project === null) {
                $this->logger->debug('dispatchNotionSync', [
                    'timesheet_id' => $timesheet->getId(),
                    'skip_reason' => 'no_project'
                ]);
                return;
            }

            $notionProjectId = $project->getMetaField('notion_project_id')?->getValue();
            
            // Build command arguments
            $arguments = [
                'php',
                $this->projectDir . '/bin/console',
                'kimai:webhook:sync-notion-timesheet',
                (string) $timesheet->getId(),
            ];
            
            if ($notionProjectId) {
                $arguments[] = $notionProjectId;
            }
            
            // Build command with output redirection to truly background it
            $command = implode(' ', $arguments) . ' > /dev/null 2>&1 &';
            
            // Use shell exec to truly detach the process
            exec($command);
            
            $this->logger->info('dispatchNotionSync', [
                'timesheet_id' => $timesheet->getId(),
                'notion_project_id' => $notionProjectId,
                'command_started' => true,
                'command' => implode(' ', $arguments)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('dispatchNotionSync', [
                'timesheet_id' => $timesheet->getId() ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Spawn a background process to update a timesheet in Notion
     */
    private function dispatchNotionUpdate($timesheet, string $notionPageId): void
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey)) {
            $this->logger->debug('dispatchNotionUpdate', [
                'timesheet_id' => $timesheet->getId(),
                'skip_reason' => 'notion_not_configured'
            ]);
            return;
        }

        try {
            // Build command arguments
            $arguments = [
                'php',
                $this->projectDir . '/bin/console',
                'kimai:webhook:update-notion-timesheet',
                (string) $timesheet->getId(),
                $notionPageId,
            ];
            
            // Build command with output redirection to truly background it
            $command = implode(' ', $arguments) . ' > /dev/null 2>&1 &';
            
            // Use shell exec to truly detach the process
            exec($command);
            
            $this->logger->info('dispatchNotionUpdate', [
                'timesheet_id' => $timesheet->getId(),
                'notion_page_id' => $notionPageId,
                'command_started' => true,
                'command' => implode(' ', $arguments)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('dispatchNotionUpdate', [
                'timesheet_id' => $timesheet->getId() ?? null,
                'notion_page_id' => $notionPageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Spawn a background process to delete a timesheet in Notion
     */
    private function dispatchNotionDelete($timesheet, string $notionPageId): void
    {
        // Skip if Notion is not configured
        if (empty($this->notionApiKey)) {
            $this->logger->debug('dispatchNotionDelete', [
                'timesheet_id' => $timesheet->getId(),
                'skip_reason' => 'notion_not_configured'
            ]);
            return;
        }

        try {
            // Build command arguments
            $arguments = [
                'php',
                $this->projectDir . '/bin/console',
                'kimai:webhook:delete-notion-timesheet',
                $notionPageId,
                (string) $timesheet->getId(),
            ];
            
            // Build command with output redirection to truly background it
            $command = implode(' ', $arguments) . ' > /dev/null 2>&1 &';
            
            // Use shell exec to truly detach the process
            exec($command);
            
            $this->logger->info('dispatchNotionDelete', [
                'timesheet_id' => $timesheet->getId(),
                'notion_page_id' => $notionPageId,
                'command_started' => true,
                'command' => implode(' ', $arguments)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('dispatchNotionDelete', [
                'timesheet_id' => $timesheet->getId() ?? null,
                'notion_page_id' => $notionPageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

