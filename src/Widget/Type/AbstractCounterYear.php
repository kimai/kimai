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
    private $systemConfiguration;
    /**
     * @var string|null
     */
    protected $titleYear;

    public function __construct(TimesheetRepository $repository, SystemConfiguration $systemConfiguration)
    {
        parent::__construct($repository);
        $this->systemConfiguration = $systemConfiguration;
    }

    public function getData(array $options = [])
    {
        $this->setBegin('01 january this year 00:00:00');
        $this->setEnd('31 december this year 23:59:59');

        if (null !== ($financialYear = $this->systemConfiguration->getFinancialYearStart())) {
            $factory = new DateTimeFactory($this->getTimezone());
            $begin = $factory->createStartOfFinancialYear($financialYear);
            $this->setBegin($begin);
            $this->setEnd($factory->createEndOfFinancialYear($begin));
            if (!empty($this->titleYear)) {
                $this->setTitle($this->titleYear);
            }
        }

        return parent::getData($options);
    }

    public function getTitle(): string
    {
        return 'stats.' . lcfirst($this->getId());
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-counter.html.twig';
    }
}
