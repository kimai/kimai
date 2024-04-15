<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class UserBillableYear extends AbstractCounterYear
{
    public function __construct(private TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        parent::__construct($systemConfiguration);
    }

    public function getId(): string
    {
        return 'userBillableYear';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-user-billable-percent.html.twig';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'money',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    protected function getYearData(\DateTimeInterface $begin, \DateTimeInterface $end, array $options = []): mixed
    {
        try {
            $Billable = $this->repository->getDurationForTimeRange($begin, $end, $this->getUser(), true);
            $AllEntries = $this->repository->getDurationForTimeRange($begin, $end, $this->getUser());
            if($AllEntries === 0) {
                return 0;
            }
            $BillablePercent = \strval(round($Billable / $AllEntries * 100, 2)) . "%";

            return $BillablePercent;
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }

    protected function getFinancialYearTitle(): string
    {
        return 'stats.billableFinancialYear';
    }
}
