<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Timesheet\TimesheetService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 */
class TimesheetStopAllCommand extends Command
{
    private $timesheetService;

    public function __construct(TimesheetService $timesheetService)
    {
        parent::__construct();
        $this->timesheetService = $timesheetService;
    }

    protected function configure(): void
    {
        $this->setName('kimai:timesheet:stop-all');
        $this->setDescription('Stop all running timesheets immediately');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $amount = $this->timesheetService->stopAll();

        if (!$output->isQuiet()) {
            $io = new SymfonyStyle($input, $output);
            $io->success(sprintf('Stopped %s timesheet records.', $amount));
        }

        return 0;
    }
}
