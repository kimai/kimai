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
use App\Timesheet\DateTimeFactory;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class UserDurationPreviousYear extends AbstractCounterYear
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        SystemConfiguration $systemConfiguration
    ) {
        parent::__construct($systemConfiguration);
    }

    public function getId(): string
    {
        return 'UserDurationPreviousYear';
    }

    protected function getFinancialYearTitle(): string
    {
        return 'stats.durationPreviousFinancialYear';
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter-duration.html.twig';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'icon' => 'duration',
            'color' => WidgetInterface::COLOR_YEAR,
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): int
    {
        $begin = $this->createPreviousYearStartDate();
        $end = $this->createPreviousYearEndDate();

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $factory = new DateTimeFactory($this->getTimezone());
            $begin = $factory->createStartOfPreviousFinancialYear($financialYear);
            $end = $factory->createEndOfPreviousFinancialYear($begin);
            $this->isFinancialYear = true;
        }

        return $this->getYearData($begin, $end, $options);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    protected function getYearData(\DateTimeInterface $begin, \DateTimeInterface $end, array $options = []): int
    {
        try {
            return $this->repository->getDurationForTimeRange($begin, $end, $this->getUser());
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
