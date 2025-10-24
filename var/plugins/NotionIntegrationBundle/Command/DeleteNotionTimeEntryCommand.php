<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Command;

use KimaiPlugin\NotionIntegrationBundle\Service\NotionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'kimai:webhook:delete-notion-timesheet',
    description: 'Delete (archive) a timesheet entry in Notion (async)'
)]
class DeleteNotionTimeEntryCommand extends Command
{
    public function __construct(
        private readonly NotionService $notionService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('notion-page-id', InputArgument::REQUIRED, 'The Notion page ID to archive')
            ->addArgument('timesheet-id', InputArgument::OPTIONAL, 'The Kimai timesheet ID (for logging)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notionPageId = $input->getArgument('notion-page-id');
        $timesheetId = $input->getArgument('timesheet-id');
        
        $this->logger->info('DeleteNotionTimeEntryCommand', [
            'notion_page_id' => $notionPageId,
            'timesheet_id' => $timesheetId,
            'step' => 'start'
        ]);

        try {
            // Archive the Notion page
            $success = $this->notionService->deleteTimeEntry($notionPageId);
            
            if ($success) {
                $this->logger->info('DeleteNotionTimeEntryCommand', [
                    'notion_page_id' => $notionPageId,
                    'timesheet_id' => $timesheetId,
                    'step' => 'success'
                ]);
                
                return Command::SUCCESS;
            } else {
                $this->logger->warning('DeleteNotionTimeEntryCommand', [
                    'notion_page_id' => $notionPageId,
                    'timesheet_id' => $timesheetId,
                    'error' => 'delete_failed'
                ]);
                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->logger->error('DeleteNotionTimeEntryCommand', [
                'notion_page_id' => $notionPageId,
                'timesheet_id' => $timesheetId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}

