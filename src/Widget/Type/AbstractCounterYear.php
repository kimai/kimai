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

abstract class AbstractCounterYear extends AbstractSimpleStatisticChart
{
    private bool $isFinancialYear = false;

    public function __construct(TimesheetRepository $repository, private SystemConfiguration $systemConfiguration)
    {
        parent::__construct($repository);
    }

    public function getData(array $options = []): mixed
    {
        $this->setBegin('01 january this year 00:00:00');
        $this->setEnd('31 december this year 23:59:59');

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $factory = new DateTimeFactory($this->getTimezone());
            $begin = $factory->createStartOfFinancialYear($financialYear);
            $this->setBegin($begin);
            $this->setEnd($factory->createEndOfFinancialYear($begin));
            $this->isFinancialYear = true;
        }

        return parent::getData($options);
    }

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
