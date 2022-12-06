<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Timesheet\Rounding\RoundingInterface;

final class RoundingService
{
    /**
     * @var array
     */
    private $rulesCache;

    /**
     * @param SystemConfiguration $configuration
     * @param RoundingInterface[] $roundingModes
     * @param array $rules
     */
    public function __construct(private SystemConfiguration $configuration, private iterable $roundingModes, private array $rules)
    {
    }

    private function getRoundingRules(): array
    {
        if (empty($this->rulesCache)) {
            $this->rulesCache = $this->rules;
            if (empty($this->rulesCache) || \array_key_exists('default', $this->rulesCache)) {
                $this->rulesCache['default']['days'] = $this->configuration->getTimesheetDefaultRoundingDays();
                $this->rulesCache['default']['begin'] = $this->configuration->getTimesheetDefaultRoundingBegin();
                $this->rulesCache['default']['end'] = $this->configuration->getTimesheetDefaultRoundingEnd();
                $this->rulesCache['default']['duration'] = $this->configuration->getTimesheetDefaultRoundingDuration();
                $this->rulesCache['default']['mode'] = $this->configuration->getTimesheetDefaultRoundingMode();
            }

            // see AppExtension, conversion from string to array due to system configuration ont allowing to store arrays
            foreach ($this->rulesCache as $key => $settings) {
                $days = explode(',', $settings['days']);
                $days = array_map('trim', $days);
                $days = array_map('strtolower', $days);
                $this->rulesCache[$key]['days'] = $days;
            }
        }

        return $this->rulesCache;
    }

    public function roundBegin(Timesheet $record): void
    {
        foreach ($this->getRoundingRules() as $rounding) {
            if ($record->getBegin() === null) {
                continue;
            }
            $weekday = $record->getBegin()->format('l');

            if (\in_array(strtolower($weekday), $rounding['days'])) {
                $rounder = $this->getRoundingMode($rounding['mode']);
                $rounder->roundBegin($record, $rounding['begin']);
            }
        }
    }

    public function roundEnd(Timesheet $record): void
    {
        foreach ($this->getRoundingRules() as $rounding) {
            if ($record->getEnd() === null) {
                continue;
            }
            $weekday = $record->getEnd()->format('l');

            if (\in_array(strtolower($weekday), $rounding['days'])) {
                $rounder = $this->getRoundingMode($rounding['mode']);
                $rounder->roundEnd($record, $rounding['end']);
            }
        }
    }

    public function roundDuration(Timesheet $record): void
    {
        foreach ($this->getRoundingRules() as $rounding) {
            if ($record->getEnd() === null) {
                continue;
            }
            $weekday = $record->getEnd()->format('l');

            if (\in_array(strtolower($weekday), $rounding['days'])) {
                $rounder = $this->getRoundingMode($rounding['mode']);
                $rounder->roundDuration($record, $rounding['duration']);
            }
        }
    }

    public function applyRoundings(Timesheet $record): void
    {
        if (null === $record->getEnd()) {
            return;
        }

        foreach ($this->getRoundingRules() as $rounding) {
            if ($record->getEnd() === null) {
                continue;
            }
            $weekday = $record->getEnd()->format('l');

            if (\in_array(strtolower($weekday), $rounding['days'])) {
                $rounder = $this->getRoundingMode($rounding['mode']);
                $rounder->roundBegin($record, $rounding['begin']);
                $rounder->roundEnd($record, $rounding['end']);

                if ($record->getBegin() !== null) {
                    $duration = $record->getEnd()->getTimestamp() - $record->getBegin()->getTimestamp();
                    $record->setDuration($duration);

                    $rounder->roundDuration($record, $rounding['duration']);
                }
            }
        }
    }

    /**
     * @return RoundingInterface[]
     */
    public function getRoundingModes(): iterable
    {
        return $this->roundingModes;
    }

    public function getRoundingMode(string $id): RoundingInterface
    {
        foreach ($this->roundingModes as $mode) {
            if ($mode->getId() === $id) {
                return $mode;
            }
        }

        throw new \InvalidArgumentException('Unknown rounding mode: ' . $id);
    }
}
