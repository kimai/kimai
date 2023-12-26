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
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class RoundingService
{
    /**
     * @var array<string, array{'days': array<string>, 'begin': int, 'end': int, 'duration': int, 'mode': string}>
     */
    private ?array $rulesCache = null;

    /**
     * @param RoundingInterface[] $roundingModes
     * @param array<string, array{'days': array<string>, 'begin': int, 'end': int, 'duration': int, 'mode': string}> $rules
     */
    public function __construct(
        private readonly SystemConfiguration $configuration,
        #[TaggedIterator(RoundingInterface::class)]
        private readonly iterable $roundingModes,
        private readonly array $rules
    )
    {
    }

    /**
     * @return array<string, array{'days': array<string>, 'begin': int, 'end': int, 'duration': int, 'mode': string}>
     */
    private function getRoundingRules(): array
    {
        if ($this->rulesCache === null) {
            $rules = $this->rules;
            $rules['default']['days'] = $this->configuration->getTimesheetDefaultRoundingDays();
            $rules['default']['begin'] = $this->configuration->getTimesheetDefaultRoundingBegin();
            $rules['default']['end'] = $this->configuration->getTimesheetDefaultRoundingEnd();
            $rules['default']['duration'] = $this->configuration->getTimesheetDefaultRoundingDuration();
            $rules['default']['mode'] = $this->configuration->getTimesheetDefaultRoundingMode();

            // see AppExtension, conversion from string to array due to system configuration not allowing to store arrays
            foreach ($rules as $key => $settings) {
                if (\is_array($settings['days'])) {
                    continue;
                }
                if ($settings['days'] === '') {
                    $rules[$key]['days'] = [];
                    continue;
                }
                $days = explode(',', $settings['days']);
                $days = array_map('trim', $days);
                $days = array_map('strtolower', $days);
                $rules[$key]['days'] = $days;
            }
            $this->rulesCache = $rules; // @phpstan-ignore-line
        }

        return $this->rulesCache; // @phpstan-ignore-line
    }

    public function roundBegin(Timesheet $record): void
    {
        foreach ($this->getRoundingRules() as $rounding) {
            if ($record->getBegin() === null) {
                continue;
            }
            $weekday = $record->getBegin()->format('l');

            if (\in_array(strtolower($weekday), $rounding['days'], true)) {
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

            if (\in_array(strtolower($weekday), $rounding['days'], true)) {
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

            if (\in_array(strtolower($weekday), $rounding['days'], true)) {
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

            if (\in_array(strtolower($weekday), $rounding['days'], true)) {
                $rounder = $this->getRoundingMode($rounding['mode']);
                $rounder->roundBegin($record, $rounding['begin']);
                $rounder->roundEnd($record, $rounding['end']);

                if ($record->getBegin() !== null) {
                    $duration = $record->getCalculatedDuration();
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
