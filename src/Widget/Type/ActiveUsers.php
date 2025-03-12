<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Model\Statistic\DateRange;
use App\Model\Statistic\StatisticDate;
use App\Repository\TimesheetRepository;
use App\Timesheet\DateTimeFactory;

final class ActiveUsers extends AbstractWidget
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly SystemConfiguration $systemConfiguration
    )
    {
    }

    private function getDataForTimeRange(?\DateTimeInterface $begin, ?\DateTimeInterface $end, bool $grouped = false): array|int // @phpstan-ignore missingType.iterableValue
    {
        $qb = $this->repository->createQueryBuilder('t');

        $qb
            ->select('COUNT(DISTINCT(t.user))')
            ->andWhere($qb->expr()->gte('t.begin', ':from'))
            ->setParameter('from', $begin)
            ->andWhere($qb->expr()->lte('t.end', ':to'))
            ->setParameter('to', $end)
        ;

        if ($grouped) {
            $qb
                ->addSelect('DATE(t.date)')
                ->addGroupBy('t.date')
            ;

            return $qb->getQuery()->getArrayResult();
        }

        $tmp = $qb->getQuery()->getSingleScalarResult();

        if (!is_numeric($tmp)) {
            return 0;
        }

        return (int) $tmp;
    }

    private function countActiveEntries(): int
    {
        $qb = $this->repository->createQueryBuilder('t');
        $qb->select($qb->expr()->count('t'))->where($qb->expr()->isNull('t.end'));

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<string, string|bool|int|float> $options
     */
    public function getData(array $options = []): mixed
    {
        $financialYear = null;
        $factory = DateTimeFactory::createByUser($this->getUser());

        if (null !== ($yearConfig = $this->systemConfiguration->getFinancialYearStart())) {
            $begin = $factory->createStartOfFinancialYear($yearConfig);
            $end = $factory->createEndOfFinancialYear($begin);
            $financialYear = $this->getDataForTimeRange($begin, $end);
        }

        $end = $factory->create('23:59:59');
        $begin = $end->modify('-6 days 00:00:00');
        /** @var array<array{1: int, 2: string}> $tmp */
        $tmp = $this->getDataForTimeRange($begin, $end, true);
        $days = new DateRange($begin, $end);
        foreach ($tmp as $item) {
            $d = new StatisticDate(new \DateTimeImmutable($item[2]));
            $d->setAmount($item[1]);
            $days->setDate($d);
        }

        return [
            'days' => $days->getDays(),
            'now' => $this->countActiveEntries(),
            'today' => $this->getDataForTimeRange($factory->createStartOfDay(), $factory->createEndOfDay()),
            'week' => $this->getDataForTimeRange($factory->getStartOfWeek(), $factory->getEndOfWeek()),
            'month' => $this->getDataForTimeRange($factory->getStartOfMonth(), $factory->getEndOfMonth()),
            'year' => $this->getDataForTimeRange($factory->createStartOfYear(), $factory->createEndOfYear()),
            'financial' => $financialYear,
        ];
    }

    public function getTitle(): string
    {
        return 'stats.activeUsers';
    }

    public function getPermissions(): array
    {
        return ['ROLE_TEAMLEAD'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-active-users.html.twig';
    }

    public function getId(): string
    {
        return 'ActiveUsers';
    }
}
