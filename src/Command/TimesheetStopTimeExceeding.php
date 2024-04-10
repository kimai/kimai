<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'kimai:timesheet:stop-time-exceeding')]
final class TimesheetStopTimeExceeding extends Command
{
    public function __construct(
        private readonly TimesheetService $timesheetService,
        private readonly TimesheetRepository $timesheetRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Stop all running timesheets, that have exceeded the specified time limit and do not yet have an end value.');
        $this->addArgument(
            'max-hours',
            InputArgument::REQUIRED,
            'Maximum number (int or decimal) of hours after which a timesheet is stopped'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeEntries = $this->timesheetRepository->getActiveEntries();
        $counter = 0;

        foreach ($activeEntries as $timesheet) {
            if ($timesheet->getEnd() === null) {
                // set temporary end, just for the calculation
                $timesheet->setEnd(new \DateTime('now', $timesheet->getBegin()?->getTimezone()));
                // calculate time diff based on seconds
                if (($timesheet->getCalculatedDuration() ?? 0) > \floatval($input->getArgument('max-hours')) * 60 * 60) {
                    // reset end value, otherwise stopTimesheet would not stop the timesheet
                    $timesheet->setEnd(null);
                    $this->timesheetService->stopTimesheet($timesheet, false);
                    $counter++;
                }
            }
        }

        if (!$output->isQuiet()) {
            $io = new SymfonyStyle($input, $output);
            $io->success(sprintf('Stopped %s timesheet records.', $counter));
        }

        return Command::SUCCESS;
    }
}
