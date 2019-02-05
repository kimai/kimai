<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Timesheet;
use App\Repository\TimesheetRepository;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command was added with v0.8.
 * Kimai saved the DateTime before with the local timezone, which can cause big problems when used in different environments.
 * You should convert all timesheet records that were saved with Kimai 2 directly, but NOT the ones migrated from Kimai v1.
 *
 * Please read https://github.com/kevinpapst/kimai2/pull/372 to find out more!
 */
class ConvertTimezoneCommand extends Command
{
    /**
     * @var TimesheetRepository
     */
    protected $repository;

    /**
     * @param TimesheetRepository $repository
     */
    public function __construct(TimesheetRepository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:convert-timezone')
            ->setDescription('Convert timesheet dates between timezones, only made for updates from 0.7 to 0.8')
            ->setHelp('This command should only be required if you imported data from Kimai v1')
            ->addOption('first-id', 'f', InputArgument::OPTIONAL, 'The database ID which should be converted first')
            ->addOption('last-id', 'l', InputArgument::OPTIONAL, 'The database ID which should be converted last')
        ;
    }

    /**
     * @param null|string $start
     * @param null|string $end
     * @return Timesheet[]
     */
    protected function getTimesheets($start = null, $end = null)
    {
        $qb = $this->repository->createQueryBuilder('t');

        $qb->select('t');
        if (!empty($start)) {
            $qb->andWhere($qb->expr()->gte('t.id', $start));
        }
        if (!empty($end)) {
            $qb->andWhere($qb->expr()->lte('t.id', $end));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Timesheet $timesheet
     * @param \DateTimeZone $timeZone
     * @return Timesheet
     */
    protected function convertTimesheet(Timesheet $timesheet, \DateTimeZone $timeZone): Timesheet
    {
        $oldTimezone = $timesheet->getTimezone();

        if (null !== $timesheet->getBegin()) {
            $beginDate = clone $timesheet->getBegin();
            $beginDate->setTimezone($timeZone);
            $timesheet->setBegin($beginDate);
        }

        if (null !== $timesheet->getEnd()) {
            $endDate = clone $timesheet->getEnd();
            $endDate->setTimezone($timeZone);
            $timesheet->setEnd($endDate);
        }

        $timesheet->setTimezone($oldTimezone);

        return $timesheet;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Type::overrideType(Type::DATETIME, DateTimeType::class);

        $io = new SymfonyStyle($input, $output);

        $start = $input->getOption('first-id');
        $end = $input->getOption('last-id');

        $result = $this->getTimesheets($start, $end);
        $amount = count($result);

        $answer = $io->ask(sprintf('This will update %s timesheet records, continue (y/n) ? ', $amount));

        if ('y' !== $answer) {
            $io->text('Aborting.');

            return;
        }

        $utc = new \DateTimeZone('UTC');
        $i = 0;

        /** @var Timesheet $timesheet */
        foreach ($result as $timesheet) {
            $timesheet = $this->convertTimesheet($timesheet, $utc);
            $this->repository->save($timesheet);

            if (++$i % 80 === 0) {
                $io->writeln('. (' . $i . '/' . $amount . ')');
            } else {
                $io->write('.');
            }
        }

        $io->writeln('. (' . $i . '/' . $amount . ')');
        $io->writeln('');
    }
}
