<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DurationFixedBeginMode implements TrackingModeInterface
{
    use TrackingModeTrait;

    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    )
    {
    }

    public function canEditBegin(): bool
    {
        return false;
    }

    public function canEditEnd(): bool
    {
        return false;
    }

    public function canEditDuration(): bool
    {
        return true;
    }

    public function canUpdateTimesWithAPI(): bool
    {
        return $this->authorizationChecker->isGranted('view_other_timesheet');
    }

    public function create(Timesheet $timesheet, ?Request $request = null): void
    {
        if (null === $timesheet->getBegin()) {
            $timesheet->setBegin(new DateTime('now', $this->getTimezone($timesheet)));
        }

        /** @var DateTime $newBegin */
        $newBegin = clone $timesheet->getBegin(); // @phpstan-ignore-line

        // this prevents the problem that "now" is being ignored in modify()
        $beginTime = new DateTime($this->configuration->getTimesheetDefaultBeginTime(), $newBegin->getTimezone());
        $newBegin->setTime((int) $beginTime->format('H'), (int) $beginTime->format('i'), 0, 0);

        $timesheet->setBegin($newBegin);
    }

    public function getId(): string
    {
        return 'duration_fixed_begin';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return false;
    }

    public function getEditTemplate(): string
    {
        return 'timesheet/edit-default.html.twig';
    }
}
