<?php


namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class UserBillableWeek extends AbstractBillablePercent
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_WEEK,], parent::getOptions($options));
    }

    public function getPermissions(): array
    {
        return ['view_own_timesheet'];
    }

    public function getId(): string
    {
        return 'userBillableWeek';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): string
    {
        try {
            $NonBillable = $this->repository->getDurationForTimeRange($this->createWeekStartDate(), $this->createWeekEndDate(), $this->getUser());
            $Billable = $this->repository->getDurationForTimeRange($this->createWeekStartDate(), $this->createWeekEndDate(), $this->getUser(), True); 
            $BillablePerc = strval(round($Billable / $NonBillable * 100)) ."%";
            return $BillablePerc;
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
