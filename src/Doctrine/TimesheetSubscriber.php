<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use App\Timesheet\CalculatorInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Timesheet;

/**
 * A listener to make sure all Timesheet entries will have a proper duration.
 */
class TimesheetSubscriber implements EventSubscriber
{
    /**
     * @var CalculatorInterface[]
     */
    protected $calculator;

    /**
     * TimesheetSubscriber constructor.
     * @param iterable $calculators
     */
    public function __construct(iterable $calculators)
    {
        foreach ($calculators as $calculator) {
            if (!($calculator instanceof CalculatorInterface)) {
                throw new \InvalidArgumentException(
                    'Invalid TimesheetCalculator implementation given. Expected CalculatorInterface but received ' .
                    get_class($calculator)
                );
            }
        }
        $this->calculator = $calculators;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->calculateFields($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    protected function calculateFields(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!($entity instanceof Timesheet)) {
            return;
        }

        foreach ($this->calculator as $calculator) {
            $calculator->calculate($entity);
        }
    }
}
