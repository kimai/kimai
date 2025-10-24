<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Command;

use App\Entity\TimesheetMeta;
use App\Repository\TimesheetRepository;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\NotionIntegrationBundle\Service\NotionService;
use KimaiPlugin\NotionIntegrationBundle\Service\WebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'kimai:webhook:sync-notion-timesheet',
    description: 'Sync a timesheet entry to Notion (async)'
)]
class SyncNotionTimeEntryCommand extends Command
{
    public function __construct(
        private readonly NotionService $notionService,
        private readonly WebhookService $webhookService,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly ?string $notionWorkspace = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('timesheet-id', InputArgument::REQUIRED, 'The timesheet ID to sync')
            ->addArgument('notion-project-id', InputArgument::OPTIONAL, 'The Notion project ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timesheetId = (int) $input->getArgument('timesheet-id');
        $notionProjectId = $input->getArgument('notion-project-id');
        
        $this->logger->info('SyncNotionTimeEntryCommand', [
            'timesheet_id' => $timesheetId,
            'notion_project_id' => $notionProjectId,
            'step' => 'start'
        ]);

        try {
            // Fetch the timesheet from the database
            $timesheet = $this->timesheetRepository->find($timesheetId);
            
            if ($timesheet === null) {
                $this->logger->warning('SyncNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'error' => 'timesheet_not_found'
                ]);
                return Command::FAILURE;
            }

            // Check if already synced
            $existingNotionId = $timesheet->getMetaField('notion_time_entry_id')?->getValue();
            if ($existingNotionId) {
                $this->logger->info('SyncNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'skip_reason' => 'already_synced',
                    'notion_page_id' => $existingNotionId
                ]);
                return Command::SUCCESS;
            }

            // Build timesheet data using the same structure as WebhookService
            $timesheetData = $this->webhookService->buildTimesheetPayload($timesheet, 'notion.sync')['timesheet'] ?? [];
            
            // Use the provided Notion project ID or get it from the project metadata
            if ($notionProjectId === null && $timesheet->getProject() !== null) {
                $notionProjectId = $timesheet->getProject()->getMetaField('notion_project_id')?->getValue();
            }
            
            // Add Notion project ID to meta fields if available
            if ($notionProjectId) {
                $timesheetData['meta_fields'] = $timesheetData['meta_fields'] ?? [];
                $timesheetData['meta_fields']['notion_project_id'] = $notionProjectId;
            }
            
            // Build Notion properties
            $properties = $this->notionService->buildTimesheetProperties($timesheetData);
            
            // Create the Notion page
            $notionPageId = $this->notionService->createTimeEntry($properties);
            
            if ($notionPageId) {
                // Store the Notion page ID in timesheet metadata
                $meta = $timesheet->getMetaField('notion_time_entry_id');
                if ($meta === null) {
                    $meta = new TimesheetMeta();
                    $meta->setName('notion_time_entry_id');
                    $meta->setIsVisible(true);
                    $timesheet->setMetaField($meta);
                }
                $meta->setValue($notionPageId);
                
                // Store the Notion link if workspace is configured
                if (!empty($this->notionWorkspace)) {
                    $linkMeta = $timesheet->getMetaField('notion_link');
                    if ($linkMeta === null) {
                        $linkMeta = new TimesheetMeta();
                        $linkMeta->setName('notion_link');
                        $linkMeta->setIsVisible(true);
                        $timesheet->setMetaField($linkMeta);
                    }
                    $linkMeta->setValue($this->buildNotionUrl($notionPageId));
                }
                
                // Persist the changes
                $this->entityManager->persist($timesheet);
                $this->entityManager->flush();
                
                $this->logger->info('SyncNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'notion_page_id' => $notionPageId,
                    'step' => 'success'
                ]);
                
                return Command::SUCCESS;
            } else {
                $this->logger->warning('SyncNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'error' => 'notion_page_id_null'
                ]);
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->logger->error('SyncNotionTimeEntryCommand', [
                'timesheet_id' => $timesheetId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
    
    /**
     * Build the Notion URL from the page ID
     */
    private function buildNotionUrl(string $pageId): string
    {
        // Remove hyphens from the page ID for the URL
        $cleanPageId = str_replace('-', '', $pageId);
        
        return sprintf('https://www.notion.so/%s/%s', $this->notionWorkspace, $cleanPageId);
    }
}

