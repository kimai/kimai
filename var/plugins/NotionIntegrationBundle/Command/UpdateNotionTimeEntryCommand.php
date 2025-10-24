<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Command;

use App\Repository\TimesheetRepository;
use KimaiPlugin\NotionIntegrationBundle\Service\NotionService;
use KimaiPlugin\NotionIntegrationBundle\Service\WebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'kimai:webhook:update-notion-timesheet',
    description: 'Update a timesheet entry in Notion (async)'
)]
class UpdateNotionTimeEntryCommand extends Command
{
    public function __construct(
        private readonly NotionService $notionService,
        private readonly WebhookService $webhookService,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('timesheet-id', InputArgument::REQUIRED, 'The timesheet ID to update')
            ->addArgument('notion-page-id', InputArgument::REQUIRED, 'The Notion page ID to update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timesheetId = (int) $input->getArgument('timesheet-id');
        $notionPageId = $input->getArgument('notion-page-id');
        
        $this->logger->info('UpdateNotionTimeEntryCommand', [
            'timesheet_id' => $timesheetId,
            'notion_page_id' => $notionPageId,
            'step' => 'start'
        ]);

        try {
            // Fetch the timesheet from the database
            $timesheet = $this->timesheetRepository->find($timesheetId);
            
            if ($timesheet === null) {
                $this->logger->warning('UpdateNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'error' => 'timesheet_not_found'
                ]);
                return Command::FAILURE;
            }

            // Build timesheet data using the same structure as WebhookService
            $timesheetData = $this->webhookService->buildTimesheetPayload($timesheet, 'notion.sync')['timesheet'] ?? [];
            
            // Get Notion project ID from the project metadata
            $notionProjectId = null;
            if ($timesheet->getProject() !== null) {
                $notionProjectId = $timesheet->getProject()->getMetaField('notion_project_id')?->getValue();
            }
            
            // Add Notion project ID to meta fields if available
            if ($notionProjectId) {
                $timesheetData['meta_fields'] = $timesheetData['meta_fields'] ?? [];
                $timesheetData['meta_fields']['notion_project_id'] = $notionProjectId;
            }
            
            // Build Notion properties
            $properties = $this->notionService->buildTimesheetProperties($timesheetData);
            
            // Update the Notion page
            $success = $this->notionService->updateTimeEntry($notionPageId, $properties);
            
            if ($success) {
                $this->logger->info('UpdateNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'notion_page_id' => $notionPageId,
                    'step' => 'success'
                ]);
                
                return Command::SUCCESS;
            } else {
                $this->logger->warning('UpdateNotionTimeEntryCommand', [
                    'timesheet_id' => $timesheetId,
                    'notion_page_id' => $notionPageId,
                    'error' => 'update_failed'
                ]);
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->logger->error('UpdateNotionTimeEntryCommand', [
                'timesheet_id' => $timesheetId,
                'notion_page_id' => $notionPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}

