<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\SystemConfiguration;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class TrackingModeService
{
    private ?TrackingModeInterface $active = null;

    /**
     * @param TrackingModeInterface[] $modes
     */
    public function __construct(
        private readonly SystemConfiguration $configuration,
        #[TaggedIterator(TrackingModeInterface::class)]
        private readonly iterable $modes
    )
    {
    }

    /**
     * @return TrackingModeInterface[]
     */
    public function getModes(): iterable
    {
        return $this->modes;
    }

    public function getActiveMode(): TrackingModeInterface
    {
        // internal caching for the current request
        // there is no use-case to change that during one requests lifetime
        if ($this->active === null) {
            $trackingMode = $this->configuration->getTimesheetTrackingMode();

            foreach ($this->getModes() as $mode) {
                if ($mode->getId() === $trackingMode) {
                    $this->active = $mode;
                    break;
                }
            }

            if ($this->active === null) {
                throw new ServiceNotFoundException($trackingMode);
            }
        }

        return $this->active;
    }
}
