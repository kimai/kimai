<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\DataProvider;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\Statistic\Day;
use App\Repository\TimesheetRepository;
use DateTime;
use DateTimeInterface;

/**
 * This class should really be deleted and replaced by TimesheetStatisticService::getDailyStatistics()
 * @deprecated since 2.0
 * @codeCoverageIgnore
 * @internal
 * @final
 */
class DailyWorkingTimeChartProvider
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * In case this method is called with one timezone and the results are from another timezone,
     * it might return rows outside the time-range.
     *
     * @param DateTimeInterface $begin
     * @param DateTimeInterface $end
     * @param User|null $user
     * @return array<mixed>
     */
    protected function getDailyData(DateTimeInterface $begin, DateTimeInterface $end, ?User $user = null): array
    {
        $qb = $this->repository->createQueryBuilder('t');

        $or = $qb->expr()->orX();
        $or->add($qb->expr()->between(':begin', 't.begin', 't.end'));
        $or->add($qb->expr()->between(':end', 't.begin', 't.end'));
        $or->add($qb->expr()->between('t.begin', ':begin', ':end'));
        $or->add($qb->expr()->between('t.end', ':begin', ':end'));

        $qb->select('t, p, a, c')
            ->andWhere($qb->expr()->isNotNull('t.end'))
            ->andWhere($or)
            ->orderBy('t.begin', 'DESC')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->leftJoin('t.activity', 'a')
            ->leftJoin('t.project', 'p')
            ->leftJoin('p.customer', 'c')
        ;

        if (null !== $user) {
            $qb
                ->andWhere($qb->expr()->eq('t.user', ':user'))
                ->setParameter('user', $user)
            ;
        }

        $timesheets = $qb->getQuery()->getResult();

        $results = [];
        /** @var Timesheet $result */
        foreach ($timesheets as $result) {
            /** @var DateTime $beginTmp */
            $beginTmp = $result->getBegin();
            /** @var DateTime $endTmp */
            $endTmp = $result->getEnd();
            $dateKeyEnd = $endTmp->format('Ymd');

            do {
                $dateKey = $beginTmp->format('Ymd');

                if ($dateKey !== $dateKeyEnd) {
                    $newDateBegin = clone $beginTmp;
                    $newDateBegin->add(new \DateInterval('P1D'));
                    // overlapping records should always start at midnight
                    $newDateBegin->setTime(0, 0, 0);
                } else {
                    $newDateBegin = clone $endTmp;
                }

                // make sure to exclude entries that are outside the requested time-range:
                // these entries can exist if you have long running entries that started before $begin
                // for statistical reasons we have to include everything between $begin and $end while
                // excluding everything that is outside of that range
                // --------------------------------------------------------------------------------------
                // Be aware that this will NOT filter every record, in case there is a timezone mismatch between the
                // begin/end dates and the ones from the database (eg. recorded in UTC) - which might actually be
                // before $begin (which happens thanks to the timezone conversion when querying the database)
                if ($newDateBegin > $begin && $beginTmp < $end) {
                    if (!isset($results[$dateKey])) {
                        $results[$dateKey] = [
                            'rate' => 0,
                            'duration' => 0,
                            'billable' => 0, // duration
                            'month' => $beginTmp->format('n'),
                            'year' => $beginTmp->format('Y'),
                            'day' => $beginTmp->format('j'),
                            'details' => []
                        ];
                    }
                    $duration = $newDateBegin->getTimestamp() - $beginTmp->getTimestamp();
                    $durationPercent = 0;
                    if ($result->getDuration() !== null && $result->getDuration() > 0) {
                        $durationPercent = $duration / $result->getDuration();
                    }
                    $rate = $result->getRate() * $durationPercent;

                    $results[$dateKey]['rate'] += $rate;
                    $results[$dateKey]['duration'] += $duration;
                    if ($result->isBillable()) {
                        $results[$dateKey]['billable'] += $duration;
                    }
                    $detailsId =
                        $result->getProject()->getCustomer()->getId()
                        . '_' . $result->getProject()->getId()
                        . '_' . $result->getActivity()->getId()
                    ;

                    if (!isset($results[$dateKey]['details'][$detailsId])) {
                        $results[$dateKey]['details'][$detailsId] = [
                            'project' => $result->getProject(),
                            'activity' => $result->getActivity(),
                            'duration' => 0,
                            'rate' => 0,
                            'billable' => 0, // duration
                        ];
                    }

                    $results[$dateKey]['details'][$detailsId]['duration'] += $duration;
                    $results[$dateKey]['details'][$detailsId]['rate'] += $rate;
                    if ($result->isBillable()) {
                        $results[$dateKey]['details'][$detailsId]['billable'] += $duration;
                    }
                }

                $beginTmp = $newDateBegin;

                // yes, we only want to compare the day, not the time
                if ((int) $end->format('Ymd') < (int) $newDateBegin->format('Ymd')) {
                    break;
                }
            } while ($dateKey !== $dateKeyEnd);
        }

        ksort($results);

        foreach ($results as $key => $value) {
            $results[$key]['details'] = array_values($results[$key]['details']);
        }

        return array_values($results);
    }

    /**
     * @deprecated since 2.0 - use TimesheetStatisticService::getDailyStatistics() instead
     *
     * @param User|null $user
     * @param DateTimeInterface $begin
     * @param DateTimeInterface $end
     * @return Day[]
     * @throws \Exception
     */
    public function getData(?User $user, DateTimeInterface $begin, DateTimeInterface $end): array
    {
        /** @var Day[] $days */
        $days = [];

        // prefill the array
        $tmp = DateTime::createFromInterface($end);
        $until = (int) $begin->format('Ymd');
        while ((int) $tmp->format('Ymd') >= $until) {
            $last = clone $tmp;
            $days[$last->format('Ymd')] = new Day($last, 0, 0.00);
            $tmp->modify('-1 day');
        }

        $results = $this->getDailyData($begin, $end, $user);

        foreach ($results as $statRow) {
            $dateTime = DateTime::createFromInterface($begin);
            $dateTime->setDate($statRow['year'], $statRow['month'], $statRow['day']);
            $dateTime->setTime(0, 0, 0);
            $day = new Day($dateTime, (int) $statRow['duration'], (float) $statRow['rate']);
            $day->setTotalDurationBillable($statRow['billable']);
            $day->setDetails($statRow['details']);
            $dateKey = $dateTime->format('Ymd');
            // make sure entries from other timezones are filtered
            if (!\array_key_exists($dateKey, $days)) {
                continue;
            }
            $days[$dateKey] = $day;
        }

        ksort($days);

        return array_values($days);
    }
}
