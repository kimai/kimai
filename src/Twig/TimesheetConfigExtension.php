<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Timesheet\TrackingModeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TimesheetConfigExtension extends AbstractExtension
{
    /**
     * @var TrackingModeInterface
     */
    protected $mode;

    public function __construct(TrackingModeService $service)
    {
        $this->mode = $service->getActiveMode();
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_punch_mode', [$this, 'isPunchInOut']),
        ];
    }

    public function isPunchInOut(): bool
    {
        return !$this->mode->canEditDuration() && !$this->mode->canEditBegin() && !$this->mode->canEditEnd();
    }
}
