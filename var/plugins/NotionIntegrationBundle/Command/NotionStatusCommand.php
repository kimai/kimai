<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\Command;

use App\Repository\TimesheetRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'kimai:webhook:notion-status',
    description: 'Show Notion sync status for recent timesheets'
)]
class NotionStatusCommand extends Command
{
    public function __construct(
        private readonly TimesheetRepository $timesheetRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of recent timesheets to show', 20);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('limit');
        
        // Get recent timesheets
        $timesheets = $this->timesheetRepository->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $table = new Table($output);
        $table->setHeaders(['ID', 'Project', 'Description', 'Duration', 'Notion ID', 'Status']);

        foreach ($timesheets as $timesheet) {
            $notionId = $timesheet->getMetaField('notion_time_entry_id')?->getValue();
            $project = $timesheet->getProject();
            $projectName = $project ? $project->getName() : '-';
            
            $status = $notionId ? '✓ Synced' : '✗ Not synced';
            
            $description = $timesheet->getDescription() ?: '-';
            if (strlen($description) > 40) {
                $description = substr($description, 0, 37) . '...';
            }
            
            $begin = $timesheet->getBegin() ? $timesheet->getBegin()->format('Y-m-d H:i') : '-';
            $end = $timesheet->getEnd() ? $timesheet->getEnd()->format('H:i') : 'active';
            $duration = $begin . ' - ' . $end;
            
            $table->addRow([
                $timesheet->getId(),
                $projectName,
                $description,
                $duration,
                $notionId ? substr($notionId, 0, 13) . '...' : '-',
                $status
            ]);
        }

        $table->render();
        
        return Command::SUCCESS;
    }
}

