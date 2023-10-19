<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Configuration\SystemConfiguration;
use App\Timesheet\DateTimeFactory;

abstract class AbstractCounterYear extends AbstractWidgetType
{
    private bool $isFinancialYear = false;

    public function __construct(private SystemConfiguration $systemConfiguration)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        $begin = $this->createDate('01 january this year 00:00:00');
        $end = $this->createDate('31 december this year 23:59:59');

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $factory = new DateTimeFactory($this->getTimezone());
            $begin = $factory->createStartOfFinancialYear($financialYear);
            $end = $factory->createEndOfFinancialYear($begin);
            $this->isFinancialYear = true;
        }

        return $this->getYearData($begin, $end, $options);
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    abstract protected function getYearData(\DateTimeInterface $begin, \DateTimeInterface $end, array $options = []): mixed;

    abstract protected function getFinancialYearTitle(): string;

    public function getTitle(): string
    {
        if ($this->isFinancialYear) {
            return $this->getFinancialYearTitle();
        }

        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
