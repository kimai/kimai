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

class CounterYear extends SimpleStatisticChart
{
    private $systemConfiguration;
    /**
     * @var string|null
     */
    protected $titleYear;

    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        parent::__construct($repository);
        $this->systemConfiguration = $systemConfiguration;
        $this->setOption('dataType', 'int');
    }

    public function getData(array $options = [])
    {
        $this->begin = '01 january this year 00:00:00';
        $this->end = '31 december this year 23:59:59';

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $factory = new DateTimeFactory($this->getTimezone());
            $this->begin = $factory->createStartOfFinancialYear($financialYear);
            $this->end = $factory->createEndOfFinancialYear($this->begin);
            if (!empty($this->titleYear)) {
                $this->setTitle($this->titleYear);
            }
        }

        return parent::getData($options);
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
